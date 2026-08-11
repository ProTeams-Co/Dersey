<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * @param  array<string, mixed>  $data
     */
    protected function beforeSave(Model $model, array $data): void
    {
        //
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

        $this->beforeSave($model, $data);
        $model->fill($data)->save();

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

        $this->beforeSave($model, $data);
        $model->fill($data)->save();

        return $this->respond($request, redirect()->route($this->routeName('index')), __('admin.crud.updated'));
    }

    public function destroy(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $model = $this->findOrFail($id);
        $this->authorize('delete', $model);

        $model->delete();

        return $this->respond($request, redirect()->route($this->routeName('index')), __('admin.crud.deleted'));
    }

    public function bulkDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', get_class($this->newModel()));

        $ids = $request->array('ids');
        $admin = Auth::guard('admin')->user();
        $deleted = 0;

        foreach ($ids as $id) {
            $model = $this->newModel()->newQuery()->find($id);

            if (! $model || $admin?->cannot('delete', $model)) {
                continue;
            }

            $model->delete();
            $deleted++;
        }

        return $this->respond(
            $request,
            redirect()->route($this->routeName('index')),
            __('admin.crud.bulk_deleted', ['count' => $deleted])
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

    protected function respond(Request $request, RedirectResponse $redirect, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return $redirect->with('status', $message);
    }
}
