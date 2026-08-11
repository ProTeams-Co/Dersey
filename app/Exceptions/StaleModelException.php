<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * Thrown by App\Support\Traits\HasOptimisticLock::saveWithVersion() when
 * its conditional UPDATE (... WHERE version = :version) matches zero rows
 * - another write already moved this exact row's version on between when
 * it was read and when this save was attempted.
 *
 * Deliberately has no render(), unlike CategoryHasDependentsException/
 * InsufficientStockException: this is an internal concurrency signal for
 * a service layer to catch and retry against a freshly re-fetched row
 * (see InventoryService), not something shown directly to an end user in
 * this batch - there's no Controller yet to decide what a
 * retry-exhausted case should look like.
 */
class StaleModelException extends Exception
{
    public function __construct(public readonly Model $model)
    {
        parent::__construct(sprintf(
            '%s #%s was modified by another request before this save completed - reload and retry.',
            class_basename($model),
            $model->getKey()
        ));
    }
}
