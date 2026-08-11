<?php

namespace App\Models;

use App\Observers\AddressObserver;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * See AddressObserver for how "one default address per user" is enforced —
 * a deliberate application-level choice, not a database constraint.
 */
#[ObservedBy([AddressObserver::class])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'label',
        'full_name',
        'phone',
        'alt_phone',
        'governorate_id',
        'city_id',
        'street',
        'building',
        'floor',
        'apartment',
        'landmark',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
