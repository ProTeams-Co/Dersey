<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Exceptions\ProductPublishBlockedException;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Rules\AttributeValueMustBeNonVariant;
use App\Rules\UniqueSlugPerLocale;
use App\Services\Catalog\ProductService;
use App\Support\Admin\AdminTable;
use App\Support\Admin\Tables\ProductsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Batch 3.2-A correction 2: no StoreProductRequest/UpdateProductRequest -
 * validation stays in rules(), the same pattern every other Batch 3.1
 * screen uses, so store()/update() keep working through
 * AdminController's beforeSave()/afterSave()/syncTranslations() hooks
 * instead of a separate FormRequest class forcing its own control flow.
 * rules() itself is dynamic: create vs update is decided by whether
 * $model exists, and on update only the keys the CURRENT request actually
 * sent get a ruleset at all (request()->has(...) checks) - that scoping
 * is the entire partial-tab-save mechanism (see ProductService's
 * docblock for the rest of it).
 *
 * store()/update() are overridden (not just relying on the base
 * versions) for exactly one reason: CLAUDE.md's "كل عملية كتابة في
 * transaction واحدة" - the base class's own store()/update() do not wrap
 * fill()->save() + syncTranslations() + afterSave() in a transaction at
 * all. The override below is the base method's body verbatim, wrapped in
 * DB::transaction() - every hook (rules(), beforeSave(), syncTranslations(),
 * afterSave()) still runs in the exact same order, nothing about the
 * pattern itself changes.
 */
class ProductsController extends AdminController
{
    private array $pendingRelations = [];

    protected function newModel(): Model
    {
        return new Product;
    }

    protected function newTable(Request $request): AdminTable
    {
        return new ProductsTable($request);
    }

    public function create(): View
    {
        return parent::create()->with([
            'brandOptions' => $this->brandOptions(),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function edit(int|string $id): View
    {
        $model = $this->findOrFail($id);

        return parent::edit($id)->with([
            'brandOptions' => $this->brandOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'nonVariantAttributes' => Attribute::where('is_variant', false)
                ->with(['translations', 'values.translations'])
                ->orderBy('sort')
                ->get(),
            'selectedAttributeValueIds' => $model->attributeValues()->pluck('attribute_values.id')->all(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function brandOptions(): array
    {
        return Brand::query()->with('translations')->orderBy('sort')->get()
            ->mapWithKeys(fn (Brand $brand) => [$brand->id => $brand->translate('ar')?->name])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function categoryOptions(): array
    {
        return Category::withDepth()->defaultOrder()->with('translations')->get()
            ->mapWithKeys(fn (Category $category) => [
                $category->id => str_repeat('— ', $category->depth).$category->translate('ar')?->name,
            ])
            ->all();
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Product::class);

        return DB::transaction(function () use ($request) {
            $model = $this->newModel();
            $model->status = ProductStatus::Draft;
            $this->sanitizeArrayInputs($request);
            $data = $request->validate($this->rules($model));
            $translations = Arr::pull($data, 'translations', []);

            $this->beforeSave($model, $data);
            $model->fill($data)->save();
            $this->syncTranslations($model, $translations);
            $this->afterSave($model, $data);

            return $this->respond($request, redirect()->route($this->routeName('index')), __('admin.crud.created'));
        });
    }

    public function update(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $model = $this->findOrFail($id);
        $this->authorize('update', $model);

        return DB::transaction(function () use ($request, $model) {
            $this->sanitizeArrayInputs($request);
            $data = $request->validate($this->rules($model));
            $translations = Arr::pull($data, 'translations', []);

            $this->beforeSave($model, $data);
            $model->fill($data)->save();
            $this->syncTranslations($model, $translations);
            $this->afterSave($model, $data);

            return $this->respond($request, redirect()->route($this->routeName('index')), __('admin.crud.updated'));
        });
    }

    /**
     * Strips a checklist's own "nothing was checked" placeholder BEFORE
     * validation ever runs. The hidden fallback <input name="category_ids[]"
     * value=""> (see _tab-basic.blade.php / _tab-attributes.blade.php)
     * exists so an "uncheck everything" submit still arrives as a real
     * array key instead of being entirely absent - but Illuminate's own
     * ConvertEmptyStringsToNull middleware turns that '' into null before
     * this method even runs (confirmed empirically: a request sending only
     * that hidden input arrives here as request()->all()['category_ids']
     * === [null], never [''] and never []).
     *
     * Cleaning it here, ahead of $request->validate(), is what lets
     * category_ids.* attribute_value_ids.* stay real, unweakened
     * integer/exists rules below: a genuinely empty array never reaches
     * them (nothing to iterate over - a `.*` rule is a no-op on an empty
     * array), while a real submitted id is still fully checked. Batch
     * 3.2-A originally dropped the `.*` rule instead, on the mistaken
     * belief that keeping it was what forced this weaker validation - the
     * actual problem was always this leftover placeholder reaching
     * validation at all.
     */
    private function sanitizeArrayInputs(Request $request): void
    {
        foreach (['category_ids', 'attribute_value_ids'] as $key) {
            if ($request->has($key)) {
                $request->merge([
                    $key => collect($request->input($key, []))
                        ->filter(fn ($id) => $id !== null && $id !== '')
                        ->values()
                        ->all(),
                ]);
            }
        }
    }

    /**
     * Pulls out everything that needs App\Services\Catalog\ProductService's
     * coordinated handling instead of a bare fill() - none of these are
     * genuine `products` scalar columns (primary_category_id IS a real
     * column, but its value is also needed to keep the categories pivot
     * consistent, which fill()->save() alone can't do).
     */
    protected function beforeSave(Model $model, array &$data): void
    {
        $categoryIds = Arr::pull($data, 'category_ids', null);
        $attributeValueIds = Arr::pull($data, 'attribute_value_ids', null);

        $this->pendingRelations = [
            'primary_category_id' => Arr::pull($data, 'primary_category_id', null),
            // sanitizeArrayInputs() already stripped the placeholder before
            // validation ran, and category_ids.*/attribute_value_ids.* are
            // real `integer` rules now - sanitizeIds() here is just int
            // casting (validated values still arrive as numeric strings,
            // never actually cast by the `integer` rule itself), not a
            // second filtering pass.
            'category_ids' => $categoryIds === null ? null : $this->sanitizeIds($categoryIds),
            'attribute_value_ids' => $attributeValueIds === null ? null : $this->sanitizeIds($attributeValueIds),
            'seo' => Arr::pull($data, 'seo', null),
            'status' => Arr::pull($data, 'status', null),
        ];
    }

    protected function afterSave(Model $model, array $data): void
    {
        $pending = array_filter($this->pendingRelations, fn ($value) => $value !== null);

        if ($pending !== []) {
            app(ProductService::class)->syncRelations($model, $pending);
        }
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return list<int>
     */
    private function sanitizeIds(array $ids): array
    {
        return array_values(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0));
    }

    /**
     * Backs the create/edit forms' live SKU-availability check
     * (admin/product-form.js) - Task 4's "تحقق فوري بـ AJAX" requirement.
     * Read-only, authorized the same as viewing the list itself.
     */
    public function skuCheck(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $sku = $request->string('sku')->toString();
        $ignoreId = $request->integer('ignore_id') ?: null;

        $available = $sku !== '' && ! Product::withTrashed()
            ->where('sku', $sku)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        return response()->json(['available' => $available]);
    }

    public function restore(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $model = Product::withTrashed()->findOrFail($id);
        $this->authorize('update', $model);

        $model->restore();

        return $this->respond($request, redirect()->route($this->routeName('index')), __('admin.crud.restored'));
    }

    public function status(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $model = $this->findOrFail($id);
        $this->authorize('update', $model);

        $data = $request->validate([
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ]);

        app(ProductService::class)->changeStatus($model, ProductStatus::from($data['status']));

        return $this->respond($request, redirect()->route($this->routeName('index')), __('admin.crud.updated'));
    }

    protected function bulkActionHandlers(): array
    {
        return [
            'deactivate' => fn (Product $product) => app(ProductService::class)->changeStatus($product, ProductStatus::Draft),
            'delete' => fn (Product $product) => $product->delete(),
        ];
    }

    /**
     * 'activate' needs its own path, not bulkActionHandlers() - a
     * publish-blocked product must be SKIPPED (and counted), not abort
     * the whole batch the way an uncaught ProductPublishBlockedException
     * from inside the base class's per-row loop would.
     */
    public function bulkAction(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->string('action')->toString() !== 'activate') {
            return parent::bulkAction($request);
        }

        $this->authorize('update', Product::class);

        $service = app(ProductService::class);
        $admin = Auth::guard('admin')->user();
        $activated = 0;
        $skipped = 0;

        foreach ($request->array('ids') as $id) {
            $product = Product::query()->find($id);

            if (! $product || Gate::forUser($admin)->denies('update', $product)) {
                continue;
            }

            try {
                $service->changeStatus($product, ProductStatus::Published);
                $activated++;
            } catch (ProductPublishBlockedException) {
                $skipped++;
            }
        }

        $message = $skipped > 0
            ? __('admin.products.bulk_activate_partial', ['activated' => $activated, 'skipped' => $skipped])
            : __('admin.crud.bulk_action_done', ['count' => $activated]);

        return $this->respond($request, redirect()->route($this->routeName('index')), $message);
    }

    protected function rules(?Model $model = null): array
    {
        if ($model === null || ! $model->exists) {
            return $this->createRules();
        }

        return $this->updateRules($model);
    }

    /**
     * Task 4 (as corrected): only the Arabic name/primary category plus
     * whatever `products` columns are NOT NULL with no default - sku,
     * base_price, gender, weight (confirmed by reading the migration).
     * status is never a rule here - beforeSave()/afterSave() never see a
     * 'status' key on create at all, so ProductService never touches it.
     * `status` itself has no DB default and no model-level default, so
     * store() assigns ProductStatus::Draft directly on the model before
     * validation runs - "programmatically", per correction 1, not via a
     * form field or a rule here.
     *
     * @return array<string, mixed>
     */
    private function createRules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'base_price' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'weight' => ['required', 'integer', 'min:1'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'primary_category_id' => ['required', 'integer', 'exists:categories,id'],
            'translations.ar.name' => ['required', 'string', 'max:255'],
            'translations.en.name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Every ruleset below is gated by request()->has(...) - a key that
     * was never sent gets no rule at all, so $request->validate() (and
     * therefore $data) never contains it, and beforeSave()/afterSave()/
     * ProductService never see it. This is the whole partial-tab-save
     * mechanism; there is no separate "which tab" parameter.
     *
     * @return array<string, mixed>
     */
    private function updateRules(Model $model): array
    {
        $request = request();
        $rules = [];

        if ($request->has('status')) {
            $rules['status'] = ['required', Rule::enum(ProductStatus::class)];
        }

        if ($request->has('is_featured')) {
            $rules['is_featured'] = ['boolean'];
        }

        if ($request->has('is_new')) {
            $rules['is_new'] = ['boolean'];
        }

        if ($request->has('primary_category_id')) {
            $rules['primary_category_id'] = ['required', 'integer', 'exists:categories,id'];
        }

        if ($request->has('category_ids')) {
            // sanitizeArrayInputs() (called before validate() runs) has
            // already stripped the checklist's own placeholder, so a
            // genuinely "nothing checked" submit reaches this as [] - a
            // `.*` rule never iterates over an empty array, so it's not a
            // failure case. A real category_ids.* rule is what actually
            // rejects a garbage id with a clean 422 instead of only
            // catching it as a raw QueryException at sync() time.
            $rules['category_ids'] = ['array'];
            $rules['category_ids.*'] = ['integer', 'exists:categories,id'];
        }

        if ($request->has('brand_id')) {
            $rules['brand_id'] = ['nullable', 'integer', 'exists:brands,id'];
        }

        if ($request->has('sku')) {
            $rules['sku'] = ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($model->id)];
        }

        if ($request->has('base_price')) {
            $rules['base_price'] = ['required', 'regex:/^\d+(\.\d{1,2})?$/'];
        }

        if ($request->has('compare_at_price')) {
            $rules['compare_at_price'] = ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'];
        }

        if ($request->has('cost_price')) {
            $rules['cost_price'] = ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'];
        }

        if ($request->has('gender')) {
            $rules['gender'] = ['required', Rule::enum(Gender::class)];
        }

        if ($request->has('season')) {
            $rules['season'] = ['nullable', 'string', 'max:255'];
        }

        if ($request->has('weight')) {
            $rules['weight'] = ['required', 'integer', 'min:1'];
        }

        foreach (['ar', 'en'] as $locale) {
            if ($request->has("translations.{$locale}")) {
                $rules["translations.{$locale}.name"] = ['required', 'string', 'max:255'];
                $rules["translations.{$locale}.slug"] = [
                    'nullable', 'string', 'max:255',
                    new UniqueSlugPerLocale('product_translations', 'product_id', $locale, __('admin.form.locale_'.$locale), $model->id),
                ];
                $rules["translations.{$locale}.short_description"] = ['nullable', 'string', 'max:500'];
                $rules["translations.{$locale}.description"] = ['nullable', 'string'];
                $rules["translations.{$locale}.material"] = ['nullable', 'string', 'max:255'];
                $rules["translations.{$locale}.care_instructions"] = ['nullable', 'string'];
            }
        }

        if ($request->has('attribute_value_ids')) {
            // Same placeholder reasoning as category_ids above. The
            // AttributeValueMustBeNonVariant rule matters here specifically
            // (category_ids has no equivalent): product_attribute_value has
            // no DB-level is_variant constraint at all (see its migration's
            // own comment) - "only non-variant attribute values get
            // attached here" was, until this rule, an application
            // convention nothing actually enforced. Without it, this field
            // could silently attach a variant-generating attribute's value
            // (size, color) to a product as a general attribute, which
            // Batch 3.2-B's variant system never expects to see there.
            $rules['attribute_value_ids'] = ['array'];
            $rules['attribute_value_ids.*'] = ['integer', 'exists:attribute_values,id', new AttributeValueMustBeNonVariant];
        }

        foreach (['ar', 'en'] as $locale) {
            if ($request->has("seo.{$locale}")) {
                $rules["seo.{$locale}.title"] = ['nullable', 'string', 'max:255'];
                $rules["seo.{$locale}.description"] = ['nullable', 'string', 'max:500'];
                $rules["seo.{$locale}.og_image"] = ['nullable', 'string'];
                $rules["seo.{$locale}.canonical_url"] = ['nullable', 'string', 'max:255'];
                $rules["seo.{$locale}.robots"] = ['nullable', Rule::in(['index, follow', 'noindex, nofollow'])];
            }
        }

        return $rules;
    }
}
