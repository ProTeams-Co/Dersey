<?php

namespace App\Models;

/**
 * Shared base for every {model}_translations row model (e.g. a future
 * ProductTranslation) — App\Support\Traits\HasTranslations is what the
 * *owning* model (Product) uses; this is what its translation rows extend
 * instead of the generic base Model.
 */
abstract class Translation extends Model
{
    /**
     * Translation rows are only ever written through the owning model's own
     * relationship (see HasTranslations::translations()), never directly
     * from request input, so there is no mass-assignment surface to guard.
     */
    protected $guarded = [];
}
