<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Every future resource controller (categories, products, ... - 3.1+)
 * extends this instead of reimplementing index/create/store/edit/update/
 * destroy/bulkDestroy from scratch. Route parameters are plain ids
 * ($id, not route-model-binding) so this stays a single generic class - a
 * subclass never needs to redeclare method signatures with its own model
 * type-hint just to get binding.
 *
 * Authorization is automatic (see authorize() calls below) via Laravel's
 * policy auto-discovery (App\Policies\{Model}Policy) - no route is left
 * unguarded, per CLAUDE.md §8/§6.
 *
 * A domain exception thrown out of delete() (e.g.
 * CategoryHasDependentsException) is deliberately NOT caught here - those
 * exceptions already implement their own render() (translated message,
 * JSON or redirect), which Laravel's exception handler calls directly,
 * bypassing the generic 500 page entirely. Catching and re-wrapping it
 * here would only get in the way of that.
 *
 * No explicit activity() calls here for create/update/delete - every
 * model extending the project's base App\Models\Model already logs its
 * own create/update/delete automatically (HasDefaultActivityLog). Adding
 * explicit calls on top would double-log every admin action; the only
 * reason that automatic logging is correctly attributed to the acting
 * admin (not silently causer-less) is AppServiceProvider's CauserResolver
 * override, which checks the `admin` guard - see its docblock.
 */
abstract class AdminController extends Controller
{
    use AuthorizesRequests;

    /**
     * Overrides AuthorizesRequests::authorize() to resolve against the
     * `admin` guard's user explicitly (authorizeForUser(), i.e.
     * Gate::forUser()) instead of the trait's default, which asks Laravel's
     * auth manager for the *default* guard's user (`web`) - always null on
     * an admin request, since the admin panel authenticates on a completely
     * separate guard/session. Without this override every policy check
     * here would silently deny everything, regardless of who's logged in.
     */
    public function authorize($ability, $arguments = [])
    {
        return $this->authorizeForUser(Auth::guard('admin')->user(), $ability, $arguments);
    }

    abstract protected function newModel(): Model;

    abstract protected function newTable(Request $request): AdminTable;

    /**
     * By reference - a subclass may need to transform validated data
     * before it's applied (e.g. Batch 3.1's Category/Brand: a media-picker
     * field submits a temporary upload's filename, which has to be moved
     * to permanent storage and swapped in before fill(), not just read).
     *
     * @param  array<string, mixed>  $data
     */
    protected function beforeSave(Model $model, array &$data): void
    {
        //
    }

    /**
     * Runs after the model (and, if present, its translations - see
     * syncTranslations() below) are saved - for anything else a subclass
     * needs to do once $model->getKey() exists (doesn't yet in
     * beforeSave()).
     *
     * @param  array<string, mixed>  $data
     */
    protected function afterSave(Model $model, array $data): void
    {
        //
    }

    /**
     * Batch 3.1 gap: none of Category/Brand/Attribute store their
     * translatable fields (name, slug, description, ...) on the model
     * itself - fill()->save() alone does nothing for them. store()/
     * update() below pull a 'translations' key (shape: [locale =>
     * [field => value]]) out of validated data automatically (before
     * fill(), which would otherwise throw - 'translations' isn't a real
     * column/fillable attribute, and preventSilentlyDiscardingAttributes()
     * is on outside production) and sync it here, one upserted row per
     * locale via the model's own translationModel(). A subclass whose
     * model isn't translatable simply never has a 'translations' key in
     * its rules() and this becomes a no-op.
     *
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function syncTranslations(Model $model, array $translations): void
    {
        if ($translations === []) {
            return;
        }

        $translationClass = $model->translationModel();
        $foreignKey = Str::snake(class_basename($model)).'_id';

        foreach ($translations as $locale => $fields) {
            $translationClass::query()->updateOrCreate(
                [$foreignKey => $model->getKey(), 'locale' => $locale],
                $fields
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(?Model $model = null): array
    {
        return [];
    }

    public function index(Request $request): JsonResponse|StreamedResponse|View
    {
        $this->authorize('viewAny', get_class($this->newModel()));

        $table = $this->newTable($request);

        if ($table->wantsJson() || $request->boolean('export')) {
            return $table->response();
        }

        return view($this->viewPath().'index', ['table' => $table]);
    }

    public function create(): View
    {
        $this->authorize('create', get_class($this->newModel()));

        return view($this->viewPath().'create', ['model' => $this->newModel()]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', get_class($this->newModel()));

        $model = $this->newModel();
        $data = $request->validate($this->rules($model));
        $translations = Arr::pull($data, 'translations', []);

        $this->beforeSave($model, $data);
        $model->fill($data)->save();
        $this->syncTranslations($model, $translations);
        $this->afterSave($model, $data);

        return $this->respond($request, redirect()->route($this->routeName('index')), __('admin.crud.created'));
    }

    public function edit(int|string $id): View
    {
        $model = $this->findOrFail($id);
        $this->authorize('update', $model);

        return view($this->viewPath().'edit', ['model' => $model]);
    }

    public function update(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $model = $this->findOrFail($id);
        $this->authorize('update', $model);

        $data = $request->validate($this->rules($model));
        $translations = Arr::pull($data, 'translations', []);

        $this->beforeSave($model, $data);
        $model->fill($data)->save();
        $this->syncTranslations($model, $translations);
        $this->afterSave($model, $data);

        return $this->respond($request, redirect()->route($this->routeName('index')), __('admin.crud.updated'));
    }

    public function destroy(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $model = $this->findOrFail($id);
        $this->authorize('delete', $model);

        $model->delete();

        return $this->respond($request, redirect()->route($this->routeName('index')), __('admin.crud.deleted'));
    }

    /**
     * Batch 3.1 gap: the base class originally only had a hardcoded
     * bulkDestroy() - Brands needed "activate"/"deactivate" bulk actions
     * too, and duplicating the same find-authorize-loop-respond shape per
     * action per controller would have meant every future resource
     * reimplementing it again. This generalizes it: a subclass declares
     * its available actions in bulkActionHandlers() (defaults to just
     * 'delete'), and this dispatches to whichever one the request names.
     *
     * @return array<string, callable(Model): void>
     */
    protected function bulkActionHandlers(): array
    {
        return [
            'delete' => fn (Model $model) => $model->delete(),
        ];
    }

    public function bulkAction(Request $request): JsonResponse|RedirectResponse
    {
        $action = $request->string('action')->toString();
        $handlers = $this->bulkActionHandlers();

        abort_unless(isset($handlers[$action]), 404);

        // Every non-delete bulk action (activate/deactivate/...) is an
        // update, authorization-wise - only 'delete' itself needs the
        // delete ability.
        $ability = $action === 'delete' ? 'delete' : 'update';

        $this->authorize($ability, get_class($this->newModel()));

        $ids = $request->array('ids');
        $admin = Auth::guard('admin')->user();
        $affected = 0;

        foreach ($ids as $id) {
            $model = $this->newModel()->newQuery()->find($id);

            if (! $model || Gate::forUser($admin)->denies($ability, $model)) {
                continue;
            }

            $handlers[$action]($model);
            $affected++;
        }

        return $this->respond(
            $request,
            redirect()->route($this->routeName('index')),
            __('admin.crud.bulk_action_done', ['count' => $affected])
        );
    }

    protected function findOrFail(int|string $id): Model
    {
        return $this->newModel()->newQuery()->findOrFail($id);
    }

    protected function viewPath(): string
    {
        return 'admin.'.Str::kebab(Str::plural(class_basename($this->newModel()))).'.';
    }

    protected function routeName(string $action): string
    {
        return 'admin.'.Str::kebab(Str::plural(class_basename($this->newModel()))).'.'.$action;
    }

    /**
     * Batch 3.1 gap: the JSON branch used to return only {message} and
     * discard $redirect entirely, with nothing on the client ever acting on
     * it - a create/edit form submitted via core/form.js's AJAX path (every
     * x-admin.form) landed the admin back on the exact same page with no
     * navigation, no visible confirmation, and a re-enabled Save button,
     * which read as "did that actually save?" and invited clicking Save
     * again. `$redirect->with('status', $message)` is called either way now
     * (flashing to session is a side effect of with(), independent of
     * whether the RedirectResponse itself is what's returned), and the
     * target URL rides along in the JSON body for core/form.js to navigate
     * to - the destination page then shows the flash via layouts/admin's
     * new session('status')/session('error') block.
     */
    protected function respond(Request $request, RedirectResponse $redirect, string $message): JsonResponse|RedirectResponse
    {
        $redirect->with('status', $message);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => $message, 'redirect' => $redirect->getTargetUrl()]);
        }

        return $redirect;
    }
}
