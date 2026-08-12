<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;
use App\Models\Brand;
use App\Rules\UniqueSlugPerLocale;
use App\Support\Admin\AdminTable;
use App\Support\Admin\Tables\BrandsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * The batch's control screen: how much does a translatable, non-
 * hierarchical resource actually need beyond AdminController/AdminTable?
 * Just rules() (validation, including per-locale slug uniqueness) and
 * beforeSave() (promoting a media-picker upload to permanent storage).
 * Everything else - listing, search, sort, filter, pagination, bulk
 * activate/deactivate/delete, CSV export, the whole create/edit/destroy
 * flow - comes from the base classes untouched.
 */
class BrandsController extends AdminController
{
    use HandlesMediaUploads;

    protected function newModel(): Model
    {
        return new Brand;
    }

    protected function newTable(Request $request): AdminTable
    {
        return new BrandsTable($request);
    }

    protected function bulkActionHandlers(): array
    {
        return [
            'activate' => fn (Brand $brand) => $brand->update(['is_active' => true]),
            'deactivate' => fn (Brand $brand) => $brand->update(['is_active' => false]),
            'delete' => fn (Brand $brand) => $brand->delete(),
        ];
    }

    protected function rules(?Model $model = null): array
    {
        $rules = [
            'logo' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ];

        foreach (['ar', 'en'] as $locale) {
            $rules["translations.{$locale}.name"] = ['required', 'string', 'max:255'];
            $rules["translations.{$locale}.slug"] = [
                'nullable', 'string', 'max:255',
                new UniqueSlugPerLocale(
                    'brand_translations',
                    'brand_id',
                    $locale,
                    __('admin.form.locale_'.$locale),
                    $model?->id,
                ),
            ];
            $rules["translations.{$locale}.description"] = ['nullable', 'string'];
        }

        return $rules;
    }

    protected function beforeSave(Model $model, array &$data): void
    {
        $data['logo'] = $this->promoteUpload($data['logo'] ?? null, 'brands');
    }
}
