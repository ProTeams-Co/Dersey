<?php

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('renders the attributes index without errors', function () {
    actingAdminWithRole();
    Attribute::factory()->count(3)->create();

    $this->get(route('admin.attributes.index'))->assertOk();
});

it('renders the create and edit pages without errors', function () {
    actingAdminWithRole();
    $attribute = Attribute::factory()->create();
    AttributeValue::factory()->for($attribute)->create();

    $this->get(route('admin.attributes.create'))->assertOk();
    $this->get(route('admin.attributes.edit', $attribute->id))->assertOk();
});

it('creates an attribute with translations for both locales', function () {
    actingAdminWithRole();

    $response = $this->post(route('admin.attributes.store'), [
        'code' => 'fabric',
        'type' => AttributeType::Text->value,
        'translations' => [
            'ar' => ['name' => 'الخامة', 'unit' => null],
            'en' => ['name' => 'Fabric', 'unit' => null],
        ],
        'is_filterable' => '1',
        'is_variant' => '0',
        'is_required' => '0',
        'is_active' => '1',
        'sort' => 0,
    ]);

    $response->assertRedirect(route('admin.attributes.index'));

    $attribute = Attribute::first();
    expect($attribute)->not->toBeNull()
        ->and($attribute->code)->toBe('fabric')
        ->and($attribute->translate('ar')->name)->toBe('الخامة')
        ->and($attribute->translate('en')->name)->toBe('Fabric');
});

it('updates an attribute and adds a new value through the repeater', function () {
    actingAdminWithRole();
    $attribute = Attribute::factory()->create(['type' => AttributeType::Color]);

    $response = $this->put(route('admin.attributes.update', $attribute->id), [
        'type' => AttributeType::Color->value,
        'translations' => [
            'ar' => ['name' => $attribute->translate('ar')->name, 'unit' => null],
            'en' => ['name' => $attribute->translate('en')->name, 'unit' => null],
        ],
        'is_filterable' => '1',
        'is_variant' => '0',
        'is_required' => '0',
        'is_active' => '1',
        'sort' => 0,
        'values' => [
            'new1' => [
                'color_hex' => '#ff0000',
                'sort' => 0,
                'delete' => '0',
                'translations' => [
                    'ar' => ['value' => 'أحمر'],
                    'en' => ['value' => 'Red'],
                ],
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.attributes.index'));

    $attribute->refresh();
    expect($attribute->values()->count())->toBe(1);

    $value = $attribute->values()->first();
    expect($value->color_hex)->toBe('#ff0000')
        ->and($value->translate('ar')->value)->toBe('أحمر')
        ->and($value->translate('en')->value)->toBe('Red');
});

it('updates an existing value and soft-deletes another through the repeater in one submission', function () {
    actingAdminWithRole();
    $attribute = Attribute::factory()->create(['type' => AttributeType::Color]);
    $keep = AttributeValue::factory()->for($attribute)->create(['color_hex' => '#000000']);
    $remove = AttributeValue::factory()->for($attribute)->create();

    $this->put(route('admin.attributes.update', $attribute->id), [
        'type' => AttributeType::Color->value,
        'translations' => [
            'ar' => ['name' => $attribute->translate('ar')->name, 'unit' => null],
            'en' => ['name' => $attribute->translate('en')->name, 'unit' => null],
        ],
        'is_filterable' => '1',
        'is_variant' => '0',
        'is_required' => '0',
        'is_active' => '1',
        'sort' => 0,
        'values' => [
            $keep->id => [
                'id' => $keep->id,
                'color_hex' => '#ffffff',
                'sort' => 0,
                'delete' => '0',
                'translations' => [
                    'ar' => ['value' => $keep->translate('ar')->value],
                    'en' => ['value' => $keep->translate('en')->value],
                ],
            ],
            $remove->id => [
                'id' => $remove->id,
                'color_hex' => $remove->color_hex,
                'sort' => 1,
                'delete' => '1',
                'translations' => [
                    'ar' => ['value' => $remove->translate('ar')->value],
                    'en' => ['value' => $remove->translate('en')->value],
                ],
            ],
        ],
    ])->assertRedirect(route('admin.attributes.index'));

    $keep->refresh();
    expect($keep->color_hex)->toBe('#ffffff')
        ->and(AttributeValue::find($remove->id))->toBeNull();
});

it('refuses to delete an attribute value that is in use by a product variant', function () {
    actingAdminWithRole();
    $attribute = Attribute::factory()->create(['type' => AttributeType::Color, 'is_variant' => true]);
    $value = AttributeValue::factory()->for($attribute)->create();
    $variant = ProductVariant::factory()->create();
    ProductVariantValue::create(['variant_id' => $variant->id, 'attribute_value_id' => $value->id]);

    $response = $this->put(route('admin.attributes.update', $attribute->id), [
        'type' => AttributeType::Color->value,
        'translations' => [
            'ar' => ['name' => $attribute->translate('ar')->name, 'unit' => null],
            'en' => ['name' => $attribute->translate('en')->name, 'unit' => null],
        ],
        'is_filterable' => '1',
        'is_variant' => '1',
        'is_required' => '0',
        'is_active' => '1',
        'sort' => 0,
        'values' => [
            $value->id => [
                'id' => $value->id,
                'color_hex' => $value->color_hex,
                'sort' => 0,
                'delete' => '1',
                'translations' => [
                    'ar' => ['value' => $value->translate('ar')->value],
                    'en' => ['value' => $value->translate('en')->value],
                ],
            ],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', __('errors.attribute_value_in_use'));
    expect(AttributeValue::find($value->id))->not->toBeNull();
});

it('deletes an attribute with no values in use', function () {
    actingAdminWithRole();
    $attribute = Attribute::factory()->create();

    $this->delete(route('admin.attributes.destroy', $attribute->id))
        ->assertRedirect(route('admin.attributes.index'));

    expect(Attribute::find($attribute->id))->toBeNull();
});

function seedAttributesForNPlusOne(int $count): void
{
    // Plain create() rather than the factory - the factory's
    // fake('ar_EG')->unique()->word() runs out of unique words once this
    // and the other tests in this file together push well past its pool
    // size. Sequential numbered names are unique without relying on
    // Faker's finite unique-word pool at all.
    foreach (range(1, $count) as $i) {
        $attribute = Attribute::create([
            'code' => "attr-{$i}-".uniqid(),
            'type' => AttributeType::Text,
            'is_filterable' => true,
            'is_variant' => false,
            'is_required' => false,
            'sort' => $i,
            'is_active' => true,
        ]);

        $attribute->translations()->create(['locale' => 'ar', 'name' => "خاصية {$i}"]);
    }
}

it('keeps a fixed query count for the attributes index regardless of row count', function () {
    actingAdminWithRole();

    seedAttributesForNPlusOne(5);
    $this->get(route('admin.attributes.index'))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('admin.attributes.index'))->assertOk();
    $queriesForFive = count(DB::getQueryLog());
    DB::disableQueryLog();

    seedAttributesForNPlusOne(95);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get(route('admin.attributes.index'))->assertOk();
    $queriesForHundred = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesForFive)->toBe($queriesForHundred);
});

it('denies a non-permitted admin from viewing attributes', function () {
    actingAdminWithRole('support');

    $this->get(route('admin.attributes.index'))->assertForbidden();
});
