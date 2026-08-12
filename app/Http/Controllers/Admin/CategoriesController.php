<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;
use App\Models\Category;
use App\Rules\UniqueSlugPerLocale;
use App\Support\Admin\AdminTable;
use App\Support\Admin\Tables\CategoriesTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * index() deliberately does NOT use AdminTable's own HTML rendering except
 * in "search mode" - see CategoriesTable's docblock for why a tree has no
 * natural fit with a paginated-rows engine. Everything else (create/store/
 * edit/update/destroy, translations, authorization) comes from
 * AdminController unchanged.
 */
class CategoriesController extends AdminController
{
    use HandlesMediaUploads;

    protected function newModel(): Model
    {
        return new Category;
    }

    protected function newTable(Request $request): AdminTable
    {
        return new CategoriesTable($request);
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $table = $this->newTable($request);

        if ($table->wantsJson() || $request->boolean('export')) {
            return $table->response();
        }

        // A search term flattens the tree into an ordinary AdminTable
        // listing (matches only, no ancestor context shown) rather than a
        // filtered-but-still-nested tree - simpler to build and reason
        // about correctly than "prune the tree to matching branches" for
        // what is, in practice, an occasional lookup rather than the
        // primary way this screen is browsed.
        if ($request->filled('q')) {
            return view('admin.categories.index', ['mode' => 'search', 'table' => $table]);
        }

        return view('admin.categories.index', [
            'mode' => 'tree',
            'roots' => Category::withDepth()->defaultOrder()->with('translations')->get()->toTree(),
            'productCounts' => Category::cumulativeProductCounts(),
            // One bulk query for the whole tree's "has its own directly
            // assigned products" fact, instead of Category::products()->
            // exists() called once per rendered node - see
            // Category::deletionBlockersFor()'s docblock for why this
            // matters (the fix for a 2-queries-per-node N+1 the tree
            // partial had before this).
            'directProductCategoryIds' => DB::table('category_product')->distinct()->pluck('category_id'),
        ]);
    }

    public function create(): View
    {
        return parent::create()->with('parentOptions', $this->parentOptions());
    }

    public function edit(int|string $id): View
    {
        return parent::edit($id)->with('parentOptions', $this->parentOptions((int) $id));
    }

    public function reorder(Request $request, int $id): JsonResponse
    {
        $this->authorize('update', Category::class);

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'before_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $category = Category::findOrFail($id);

        $forbiddenParents = $category->descendants()->pluck('id')->push($category->id);

        if (($data['parent_id'] ?? null) && $forbiddenParents->contains($data['parent_id'])) {
            abort(422, __('admin.categories.cannot_move_into_own_descendant'));
        }

        if ($data['before_id'] ?? null) {
            $category->insertBeforeNode(Category::findOrFail($data['before_id']));
        } elseif ($data['parent_id'] ?? null) {
            $category->appendToNode(Category::findOrFail($data['parent_id']))->save();
        } else {
            $category->saveAsRoot();
        }

        return response()->json(['message' => __('admin.crud.updated')]);
    }

    protected function rules(?Model $model = null): array
    {
        $rules = [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'image' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'show_in_menu' => ['boolean'],
        ];

        if ($model?->exists) {
            // A category can't become its own parent or its own
            // descendant's child - the same guard reorder() applies to
            // drag-and-drop also applies to the plain edit form's parent
            // select.
            $forbidden = $model->descendants()->pluck('id')->push($model->id)->all();
            $rules['parent_id'][] = 'not_in:'.implode(',', $forbidden);
        }

        foreach (['ar', 'en'] as $locale) {
            $rules["translations.{$locale}.name"] = ['required', 'string', 'max:255'];
            $rules["translations.{$locale}.slug"] = [
                'nullable', 'string', 'max:255',
                new UniqueSlugPerLocale(
                    'category_translations',
                    'category_id',
                    $locale,
                    __('admin.form.locale_'.$locale),
                    $model?->id,
                ),
            ];
            $rules["translations.{$locale}.description"] = ['nullable', 'string'];
            $rules["translations.{$locale}.meta_title"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$locale}.meta_description"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    protected function beforeSave(Model $model, array &$data): void
    {
        $data['image'] = $this->promoteUpload($data['image'] ?? null, 'categories');
        $data['icon'] = $this->promoteUpload($data['icon'] ?? null, 'categories');
    }

    /**
     * Flat, indentation-prefixed `id => label` pairs for the parent <select>
     * (x-form.select's own contract - it isn't tree-aware) - excludes the
     * category being edited and all of its own descendants, the same set
     * already enforced server-side by rules()'s `not_in:` rule.
     *
     * @return array<int, string>
     */
    private function parentOptions(?int $excludeId = null): array
    {
        $excludedIds = [];

        if ($excludeId !== null) {
            $excludedIds = Category::findOrFail($excludeId)->descendants()->pluck('id')->push($excludeId)->all();
        }

        return Category::withDepth()->defaultOrder()->with('translations')->get()
            ->reject(fn (Category $category) => in_array($category->id, $excludedIds, true))
            ->mapWithKeys(fn (Category $category) => [
                $category->id => str_repeat('— ', $category->depth).$category->translate('ar')?->name,
            ])
            ->all();
    }
}
