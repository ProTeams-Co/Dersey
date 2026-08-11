<?php

namespace App\Support\Traits;

use App\Exceptions\StaleModelException;

/**
 * Optimistic locking via a `version` column, for models where two
 * requests can race to write the same row - stock is the motivating case:
 * two concurrent "buy" attempts against one variant must not both succeed
 * off the same in-memory stock count. saveWithVersion() issues a
 * conditional UPDATE ... WHERE version = :version-it-was-read-with; if a
 * different write already moved the version on in between, the UPDATE
 * matches zero rows and this throws StaleModelException instead of
 * silently clobbering the other write. The caller (InventoryService) is
 * expected to catch it, re-fetch a fresh row, and retry.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasOptimisticLock
{
    /**
     * Bumps `version` on the in-memory model to one past what it was
     * loaded with - saveWithVersion() calls this itself, so every
     * version-checked save increments it; exposed as its own method so it
     * can be asserted on directly.
     */
    public function incrementVersion(): void
    {
        $this->version = $this->getOriginal('version') + 1;
    }

    /**
     * Like save(), but for an existing row, the UPDATE is conditioned on
     * `version` still matching what this instance was loaded with.
     *
     * @throws StaleModelException if another write already changed this row's version
     */
    public function saveWithVersion(): bool
    {
        if (! $this->exists) {
            return $this->save();
        }

        // Checked before incrementVersion(), not after: incrementVersion()
        // itself dirties `version`, so a check placed after it can never
        // see an empty diff - a no-op call would still issue a write and
        // bump the version for no reason, invalidating any other request
        // that already read this row. (Caught in review, not by a failing
        // test - the old order made the "if dirty === []" branch dead code.)
        if ($this->getDirty() === []) {
            return true;
        }

        $versionReadAt = $this->getOriginal('version');
        $this->incrementVersion();

        $dirty = $this->getDirty();

        $affected = static::query()
            ->where($this->getKeyName(), $this->getKey())
            ->where('version', $versionReadAt)
            ->update($dirty);

        if ($affected === 0) {
            throw new StaleModelException($this);
        }

        foreach (array_keys($dirty) as $attribute) {
            $this->syncOriginalAttribute($attribute);
        }

        return true;
    }
}
