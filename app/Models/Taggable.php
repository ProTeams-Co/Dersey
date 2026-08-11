<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Backing model for the `taggables` pivot table - not used directly by
 * callers, who go through Tag::posts()/Post::tags() (morphedByMany /
 * morphToMany) instead. Mirrors Couponable's role for the couponables table.
 */
class Taggable extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tag_id',
        'taggable_type',
        'taggable_id',
    ];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}
