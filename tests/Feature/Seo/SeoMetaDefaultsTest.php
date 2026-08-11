<?php

use App\Models\Product;
use App\Models\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('falls back to the model default title/description when no custom SeoMeta row exists', function () {
    $product = Product::factory()->create();
    $translation = $product->translate('ar');

    $meta = $product->seoMeta('ar');

    expect($meta->title)->toBe($translation->name)
        ->and($meta->description)->not->toBeNull()
        ->and($meta->robots)->toBe('index, follow');
});

it('uses the custom SeoMeta row when one exists, falling back field by field', function () {
    $product = Product::factory()->create();

    SeoMeta::create([
        'seoable_type' => Product::class,
        'seoable_id' => $product->id,
        'locale' => 'ar',
        'title' => 'عنوان مخصص للـ SEO',
        'description' => null,
        'robots' => 'noindex, nofollow',
    ]);

    $meta = $product->seoMeta('ar');

    expect($meta->title)->toBe('عنوان مخصص للـ SEO')
        ->and($meta->description)->toBe($product->defaultSeoDescription('ar'))
        ->and($meta->robots)->toBe('noindex, nofollow');
});
