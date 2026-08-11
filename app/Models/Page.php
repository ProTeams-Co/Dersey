<?php

namespace App\Models;

use App\Support\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasTranslations, SoftDeletes;

    protected $fillable = [
        'template',
        'is_active',
        'show_in_footer',
        'sort',
    ];

    protected array $translatable = ['title', 'slug', 'content', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_footer' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function translationModel(): string
    {
        return PageTranslation::class;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInFooter(Builder $query): Builder
    {
        return $query->where('show_in_footer', true);
    }
}
