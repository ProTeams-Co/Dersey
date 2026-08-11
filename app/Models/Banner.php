<?php

namespace App\Models;

use App\Enums\BannerPosition;
use App\Support\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;

class Banner extends Model
{
    use HasTranslations;

    protected $fillable = [
        'position',
        'image',
        'image_mobile',
        'link',
        'starts_at',
        'ends_at',
        'sort',
        'is_active',
        'clicks_count',
    ];

    protected array $translatable = ['title', 'subtitle', 'button_text', 'alt'];

    protected function casts(): array
    {
        return [
            'position' => BannerPosition::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'sort' => 'integer',
            'is_active' => 'boolean',
            'clicks_count' => 'integer',
        ];
    }

    public function translationModel(): string
    {
        return BannerTranslation::class;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeAtPosition(Builder $query, BannerPosition $position): Builder
    {
        return $query->where('position', $position);
    }
}
