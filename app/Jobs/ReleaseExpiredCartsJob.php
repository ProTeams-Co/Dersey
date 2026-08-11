<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Services\Cart\CartService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Releases every expired cart's stock reservations and deletes the cart -
 * meant to run on a schedule (e.g. hourly), not on-demand. Reuses
 * CartService::clear() rather than reimplementing the release loop, so
 * "what happens when a cart's items go away" stays defined in one place.
 */
class ReleaseExpiredCartsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(CartService $cartService): void
    {
        Cart::query()
            ->where('expires_at', '<=', now())
            ->with('items')
            ->chunkById(100, function ($carts) use ($cartService) {
                foreach ($carts as $cart) {
                    $cartService->clear($cart);
                    $cart->delete();
                }
            });
    }
}
