<?php

namespace App\Observers;

use App\Models\Address;
use Illuminate\Support\Facades\DB;

/**
 * Enforces "one default address per user" at the application level — see
 * Address::class-level decision note (and the batch report) for why this
 * was chosen over a MySQL/MariaDB partial-unique-index emulation: neither
 * database supports a real partial unique index, the generated-column
 * workaround interacts awkwardly with soft deletes (a soft-deleted "default"
 * row would keep occupying the constraint unless the expression also
 * filters on deleted_at), and the desired UX — silently swap the default,
 * not reject the write — wants application logic either way.
 *
 * Runs on `saved`, not `saving`: the new default is persisted first, then
 * every other address for the same user is unset in the same transaction,
 * so a failure partway through never leaves two defaults (or zero) — only
 * "before" and "after", never in between.
 */
class AddressObserver
{
    public function saved(Address $address): void
    {
        if (! $address->is_default) {
            return;
        }

        DB::transaction(function () use ($address) {
            Address::where('user_id', $address->user_id)
                ->where('id', '!=', $address->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        });
    }
}
