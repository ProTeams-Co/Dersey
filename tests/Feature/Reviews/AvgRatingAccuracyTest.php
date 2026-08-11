<?php

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps avg_rating exact after adding 10 approved reviews then deleting 3', function () {
    $product = Product::factory()->create();
    $users = User::factory()->count(10)->create();

    $ratings = [5, 4, 3, 5, 2, 4, 5, 1, 3, 4];
    $reviews = [];

    foreach ($ratings as $i => $rating) {
        $reviews[] = Review::create([
            'product_id' => $product->id,
            'user_id' => $users[$i]->id,
            'rating' => $rating,
            'comment' => 'تعليق تجريبي',
            'status' => ReviewStatus::Approved,
        ]);
    }

    // Delete 3 specific reviews (ratings 5, 2, 3 - indexes 0, 4, 8).
    $reviews[0]->delete();
    $reviews[4]->delete();
    $reviews[8]->delete();

    $remaining = array_values(array_diff_key($ratings, array_flip([0, 4, 8])));
    $expectedAverage = round(array_sum($remaining) / count($remaining), 2);

    $product->refresh();

    expect($product->reviews_count)->toBe(count($remaining))
        ->and((float) $product->avg_rating)->toBe($expectedAverage);
});

it('matches a from-scratch manual recalculation, not an incremental running average', function () {
    $product = Product::factory()->create();
    $users = User::factory()->count(6)->create();

    foreach ([5, 5, 4, 3, 2, 5] as $i => $rating) {
        Review::create([
            'product_id' => $product->id,
            'user_id' => $users[$i]->id,
            'rating' => $rating,
            'comment' => 'تعليق',
            'status' => ReviewStatus::Approved,
        ]);
    }

    $manual = $product->reviews()->where('status', ReviewStatus::Approved)->avg('rating');

    $product->refresh();

    expect((float) $product->avg_rating)->toBe(round($manual, 2));
});
