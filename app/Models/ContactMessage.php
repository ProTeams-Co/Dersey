<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Builder;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'admin_reply',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
        ];
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', ContactMessageStatus::New);
    }
}
