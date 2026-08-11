<?php

namespace App\Models;

use App\Enums\RedirectStatusCode;
use Illuminate\Database\Eloquent\Builder;

class Redirect extends Model
{
    protected $fillable = [
        'from_path',
        'to_path',
        'status_code',
        'hits',
        'last_hit_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => RedirectStatusCode::class,
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
