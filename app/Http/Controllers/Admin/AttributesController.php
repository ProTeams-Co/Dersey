<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Support\Admin\AdminTable;
use App\Support\Admin\Tables\AttributesTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Values are not their own resource controller (no AttributeValuesController,
 * no separate routes) - Task 3's spec is explicit that the attribute and its
 * values live on one page/one form submission. AdminController's own
 * 'translations' extraction pattern (see its docblock) is reused here for a
 * second, attribute-specific key ('values') via the same beforeSave()/
 * afterSave() hooks rather than overriding store()/update() outright.
 */
class AttributesController extends AdminController
{
    protected function newModel(): Model
    {
        return new Attribute;
    }

    protected function newTable(Request $request): AdminTable
    {
        return new AttributesTable($request);
    }

    protected function rules(?Model $model = null): array
    {
        $rules = [
            'type' => ['required', Rule::enum(AttributeType::class)],
            'is_filterable' => ['boolean'],
            'is_variant' => ['boolean'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],

            'values' => ['array'],
            'values.*.id' => ['nullable', 'integer', 'exists:attribute_values,id'],
            'values.*.color_hex' => ['nullable', 'string', 'max:7'],
            'values.*.sort' => ['nullable', 'integer', 'min:0'],
            'values.*.delete' => ['nullable', 'boolean'],
        ];

        // code is set once at creation and never editable again (Task 3's
        // explicit "read-only after creation - changing it breaks
        // variants") - simply not validated/accepted on update, so a
        // tampered request can't slip a new code through fill().
        if (! $model?->exists) {
            $rules['code'] = ['required', 'string', 'max:100', 'alpha_dash', 'unique:attributes,code'];
        }

        foreach (['ar', 'en'] as $locale) {
            $rules["translations.{$locale}.name"] = ['required', 'string', 'max:255'];
            $rules["translations.{$locale}.unit"] = ['nullable', 'string', 'max:50'];
            $rules["values.*.translations.{$locale}.value"] = ['required_without:values.*.delete', 'nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    private array $pendingValues = [];

    protected function beforeSave(Model $model, array &$data): void
    {
        $this->pendingValues = Arr::pull($data, 'values', []);
    }

    protected function afterSave(Model $model, array $data): void
    {
        $this->syncValues($model, $this->pendingValues);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncValues(Attribute $attribute, array $rows): void
    {
        foreach ($rows as $row) {
            $translations = $row['translations'] ?? [];
            $attributes = [
                'color_hex' => $row['color_hex'] ?? null,
                'sort' => $row['sort'] ?? 0,
            ];

            if (! empty($row['id'])) {
                $value = AttributeValue::query()->find($row['id']);

                if (! $value) {
                    continue;
                }

                if (! empty($row['delete'])) {
                    // May throw AttributeValueInUseException - deliberately
                    // not caught here, same as CategoryHasDependentsException
                    // in AdminController::destroy(): the exception's own
                    // render() already produces a translated 422/redirect.
                    $value->delete();

                    continue;
                }

                $value->fill($attributes)->save();
            } else {
                if (! empty($row['delete'])) {
                    // A row added and removed again client-side before
                    // submit - never existed, nothing to do.
                    continue;
                }

                $value = new AttributeValue(['attribute_id' => $attribute->id, ...$attributes]);
                $value->save();
            }

            $this->syncTranslations($value, $translations);
        }
    }
}
