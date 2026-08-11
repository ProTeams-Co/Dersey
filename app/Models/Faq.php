<?php

namespace App\Models;

use App\Support\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    use HasTranslations;

    protected $fillable = [
        'faq_category_id',
        'sort',
        'is_active',
    ];

    protected array $translatable = ['question', 'answer'];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function translationModel(): string
    {
        return FaqTranslation::class;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
