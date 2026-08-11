<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewHelpfulVote;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    private const TOTAL_REVIEWS = 40;

    public function run(): void
    {
        if (Review::query()->exists()) {
            return;
        }

        // Enough distinct authors for realistic, varied reviews - reused
        // across products, not one row per product.
        $userCount = User::query()->count();
        if ($userCount < 15) {
            User::factory()->count(15 - $userCount)->create();
        }

        $userIds = User::query()->pluck('id')->all();
        $productIds = Product::query()->pluck('id')->all();

        if ($productIds === [] || $userIds === []) {
            return;
        }

        // Real verified-purchase reviews: one per order item belonging to
        // an actually delivered order, so is_verified_purchase has genuine
        // ground truth to compute from, not just random flags.
        $deliveredItems = OrderItem::query()
            ->with('order')
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Delivered))
            ->get(['id', 'product_id', 'order_id']);

        $reviewed = [];
        $created = 0;

        foreach ($deliveredItems as $item) {
            $order = $item->order;
            $key = $item->product_id.':'.$order->user_id;

            if (isset($reviewed[$key])) {
                continue;
            }

            $reviewed[$key] = true;

            Review::create([
                'product_id' => $item->product_id,
                'user_id' => $order->user_id,
                'order_item_id' => $item->id,
                'rating' => random_int(4, 5),
                'title' => 'منتج ممتاز',
                'comment' => 'استلمت الطلب بسرعة والجودة فوق التوقعات، هكرر التجربة أكيد.',
                'status' => ReviewStatus::Approved,
            ]);

            $created++;
        }

        // Remaining reviews: not tied to a specific order (order_item_id
        // null), varied ratings/statuses across random product+user pairs.
        $attempts = 0;

        while ($created < self::TOTAL_REVIEWS && $attempts < self::TOTAL_REVIEWS * 5) {
            $attempts++;

            $productId = $productIds[array_rand($productIds)];
            $userId = $userIds[array_rand($userIds)];
            $key = $productId.':'.$userId.':null';

            if (isset($reviewed[$key])) {
                continue;
            }

            $reviewed[$key] = true;

            [$rating, $comment] = $this->randomFeedback();

            Review::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'order_item_id' => null,
                'rating' => $rating,
                'title' => $rating >= 4 ? 'راضي جدًا عن المنتج' : ($rating === 3 ? 'المنتج عادي' : 'مش زي ما توقعت'),
                'comment' => $comment,
                'status' => $this->randomStatus(),
            ]);

            $created++;
        }

        $this->seedHelpfulVotes($userIds);
    }

    /**
     * @param  list<int>  $userIds
     */
    private function seedHelpfulVotes(array $userIds): void
    {
        $approvedReviewIds = Review::query()
            ->where('status', ReviewStatus::Approved)
            ->inRandomOrder()
            ->limit(15)
            ->pluck('id');

        foreach ($approvedReviewIds as $reviewId) {
            $voters = collect($userIds)->shuffle()->take(random_int(1, 5));

            foreach ($voters as $voterId) {
                ReviewHelpfulVote::firstOrCreate([
                    'review_id' => $reviewId,
                    'user_id' => $voterId,
                ]);
            }
        }
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function randomFeedback(): array
    {
        $options = [
            [5, 'المنتج جودته عالية جدًا والخامة ممتازة، مقاس مظبوط تمامًا.'],
            [5, 'تجربة شراء رائعة، التوصيل كان سريع والمنتج زي الصورة بالظبط.'],
            [4, 'المنتج حلو بس التوصيل اتأخر شوية عن الموعد المتوقع.'],
            [4, 'خامة كويسة والمقاس مناسب، هطلب تاني أكيد.'],
            [3, 'المنتج عادي، مكنش زي ما كنت متوقع من الصور.'],
            [3, 'اللون مختلف شوية عن اللي في الموقع.'],
            [2, 'الخامة أقل من المتوسط والمقاس أصغر من المكتوب.'],
            [5, 'من أحسن المنتجات اللي جربتها من ديرسي، تحفة.'],
        ];

        return $options[array_rand($options)];
    }

    private function randomStatus(): ReviewStatus
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 75 => ReviewStatus::Approved,
            $roll <= 90 => ReviewStatus::Pending,
            default => ReviewStatus::Rejected,
        };
    }
}
