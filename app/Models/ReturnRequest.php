<?php

namespace App\Models;

use App\Enums\ReturnRequestStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'reason',
        'items',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReturnRequestStatus::class,
            'items' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
