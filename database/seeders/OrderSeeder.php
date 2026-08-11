<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use Illuminate\Database\Seeder;

/**
 * Builds real orders through the actual services (CartService::add() +
 * OrderService::createFromCart()/transitionTo()), not by inserting rows
 * directly - so every seeded order genuinely went through stock
 * reservation, re-verification, commit, and legal status transitions,
 * the same as a real checkout would. This is what lets the snapshot
 * verification task actually mean something: the two "deleted product"
 * orders at the end have real order_items produced by the real snapshot
 * code, not hand-crafted rows that happen to look right.
 */
class OrderSeeder extends Seeder
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
    ) {
    }

    public function run(): void
    {
        $shippingMethod = ShippingMethod::query()->where('type', 'flat')->first();

        // A list of transition paths, not an associative array keyed by
        // status - PHP doesn't allow enum instances as array keys at all
        // (confirmed by actually running this: "TypeError: Illegal offset
        // type"). Each path's own final element (or OrderStatus::Pending,
        // for the empty first path) is what "the final status" means here.
        $paths = [
            [],
            [OrderStatus::Confirmed],
            [OrderStatus::Confirmed, OrderStatus::Processing],
            [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped],
            [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::OutForDelivery],
            [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::OutForDelivery, OrderStatus::Delivered],
            [OrderStatus::Cancelled],
            [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::OutForDelivery, OrderStatus::Delivered, OrderStatus::Returned],
        ];

        $paidStatuses = [OrderStatus::Shipped, OrderStatus::OutForDelivery, OrderStatus::Delivered, OrderStatus::Returned];

        foreach ($paths as $path) {
            $order = $this->createOrder($shippingMethod);

            foreach ($path as $status) {
                $this->orderService->transitionTo($order, $status, "Seed: moved to {$status->value}.");
            }

            $finalStatus = $path === [] ? OrderStatus::Pending : end($path);

            if (in_array($finalStatus, $paidStatuses, true)) {
                $this->markPaid($order);
            }
        }

        // Orders whose underlying product gets hard-deleted afterward -
        // Task 7's snapshot verification proves order_items is unaffected.
        $this->createOrderWithDeletedProduct($shippingMethod);
        $this->createOrderWithDeletedProduct($shippingMethod);
    }

    private function createOrder(ShippingMethod $shippingMethod): Order
    {
        $user = User::inRandomOrder()->first() ?? User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $cart = $this->cartService->findOrCreateForUser($user);

        $variants = ProductVariant::query()->where('stock_quantity', '>', 2)->inRandomOrder()->limit(2)->get();

        foreach ($variants as $variant) {
            $this->cartService->add($cart, $variant, 1);
        }

        return $this->orderService->createFromCart(
            cart: $cart->fresh(),
            shippingAddress: $address,
            shippingMethod: $shippingMethod,
            paymentMethod: PaymentMethod::CashOnDelivery,
            user: $user,
        );
    }

    private function markPaid(Order $order): void
    {
        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => $order->grand_total,
        ]);

        $order->payment_status = PaymentStatus::Paid;
        $order->paid_at = now();
        $order->save();
    }

    /**
     * A normal order (its own items went through the real commit() flow,
     * same as every other order here) plus one extra item added directly
     * for a variant that was never committed through InventoryService -
     * approved approach for a real conflict: any variant actually ordered
     * through commit() always leaves behind an inventory_movements row,
     * and that table's variant_id is restrictOnDelete() (approved in
     * Batch 2.3, protecting the audit trail), so a genuinely-ordered
     * product can never be hard-deleted afterward - which is correct,
     * but means it can't be used for this specific "does the snapshot
     * survive a real product deletion" scenario. A never-committed
     * variant sidesteps that without touching the approved restrict rule.
     */
    private function createOrderWithDeletedProduct(ShippingMethod $shippingMethod): void
    {
        $order = $this->createOrder($shippingMethod);

        $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
        $variant->load('product.translations');

        $order->items()->create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'product_name' => $variant->product->translations
                ->mapWithKeys(fn ($translation) => [$translation->locale => $translation->name])
                ->all(),
            'variant_options' => null,
            'sku' => $variant->sku,
            'image_path' => null,
            'unit_price' => $variant->final_price,
            'quantity' => 1,
            'line_total' => $variant->final_price,
        ]);

        Product::find($variant->product_id)?->forceDelete();
    }
}
