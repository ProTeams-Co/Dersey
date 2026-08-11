<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'is_active',
        'subscribed_at',
        'unsubscribed_at',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
