<?php

namespace App\Support\Admin\Tables;

use App\Models\Attribute;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Builder;

class AttributesTable extends AdminTable
{
    public function columns(): array
    {
        return [
            ['key' => 'code', 'label' => 'admin.attributes.column_code', 'sortable' => true, 'searchable' => true],
            // translatable: true - see BrandsTable::columns()'s docblock on
            // the same 'name' column / AdminTable::applyTranslatedSort().
            ['key' => 'name', 'label' => 'admin.attributes.column_name', 'sortable' => true, 'translatable' => true, 'searchable' => true],
            [
                'key' => 'type',
                'label' => 'admin.attributes.column_type',
                'sortable' => true,
                'format' => fn (Attribute $attribute) => $this->badge($attribute->type->label()),
            ],
            [
                'key' => 'is_filterable',
                'label' => 'admin.attributes.column_is_filterable',
                'sortable' => true,
                'align' => 'center',
                'format' => fn (Attribute $attribute) => $this->booleanBadge($attribute->is_filterable),
            ],
            [
                'key' => 'is_variant',
                'label' => 'admin.attributes.column_is_variant',
                'sortable' => true,
                'align' => 'center',
                'format' => fn (Attribute $attribute) => $this->booleanBadge($attribute->is_variant),
            ],
            [
                'key' => 'values_count',
                'label' => 'admin.attributes.column_values_count',
                'align' => 'center',
                'format' => fn (Attribute $attribute) => (string) $attribute->values_count,
            ],
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'is_filterable', 'type' => 'boolean', 'label' => 'admin.attributes.column_is_filterable'],
            ['key' => 'is_variant', 'type' => 'boolean', 'label' => 'admin.attributes.column_is_variant'],
        ];
    }

    public function query(): Builder
    {
        return Attribute::query()->withCount('values');
    }

    public function translatedSearchColumns(): array
    {
        return ['name'];
    }

    public function with(): array
    {
        return ['translations'];
    }

    public function defaultSort(): array
    {
        return ['key' => 'sort', 'direction' => 'asc'];
    }

    public function rowActions(): array
    {
        return [
            [
                'key' => 'edit',
                'label' => 'admin.table.actions_edit',
                'icon' => 'pencil',
                'url' => fn (Attribute $attribute) => route('admin.attributes.edit', $attribute->id),
                'permission' => 'attributes.update',
            ],
            [
                'key' => 'delete',
                'label' => 'admin.table.actions_delete',
                'icon' => 'trash',
                'url' => fn (Attribute $attribute) => route('admin.attributes.destroy', $attribute->id),
                'method' => 'delete',
                'permission' => 'attributes.delete',
                'confirm' => true,
            ],
        ];
    }
}
