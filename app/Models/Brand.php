<?php

namespace App\Models;

use App\Observers\BrandObserver;
use App\Support\Traits\HasTranslations;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BrandObserver::class])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = [
        'logo',
        'is_active',
        'is_featured',
        'sort',
    ];

    protected array $translatable = ['name', 'slug', 'description'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function translationModel(): string
    {
        return BrandTranslation::class;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
