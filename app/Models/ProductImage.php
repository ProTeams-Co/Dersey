<?php

namespace App\Models;

use App\Observers\ProductImageObserver;
use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations as HasJsonTranslations;

/**
 * `alt` uses spatie/laravel-translatable (JSON column), not this project's
 * own separate-table App\Support\Traits\HasTranslations - alt text is
 * reference data, never searched/indexed, same reasoning as
 * Governorate/City names. Aliased on import since both traits share the
 * name HasTranslations in different namespaces (see CLAUDE.md).
 */
#[ObservedBy([ProductImageObserver::class])]
class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory, HasJsonTranslations, SoftDeletes;

    public array $translatable = ['alt'];

    protected $fillable = [
        'product_id',
        'color_value_id',
        'path',
        'alt',
        'sort',
        'is_primary',
        'width',
        'height',
        'blurhash',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_primary' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function colorValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'color_value_id');
    }
}
