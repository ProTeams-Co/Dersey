<?php

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('only counts approved reviews toward avg_rating/reviews_count', function () {
    $product = Product::factory()->create();
    $users = User::factory()->count(4)->create();

    Review::create(['product_id' => $product->id, 'user_id' => $users[0]->id, 'rating' => 5, 'comment' => 'x', 'status' => ReviewStatus::Approved]);
    Review::create(['product_id' => $product->id, 'user_id' => $users[1]->id, 'rating' => 5, 'comment' => 'x', 'status' => ReviewStatus::Approved]);
    Review::create(['product_id' => $product->id, 'user_id' => $users[2]->id, 'rating' => 1, 'comment' => 'x', 'status' => ReviewStatus::Pending]);
    Review::create(['product_id' => $product->id, 'user_id' => $users[3]->id, 'rating' => 1, 'comment' => 'x', 'status' => ReviewStatus::Rejected]);

    $product->refresh();

    // Pending/rejected (rating 1 each) must not drag the average down.
    expect($product->reviews_count)->toBe(2)
        ->and((float) $product->avg_rating)->toBe(5.0);
});

it('reverts avg_rating when an approved review is rejected, and updates it when a pending one is approved', function () {
    $product = Product::factory()->create();
    $users = User::factory()->count(2)->create();

    $review = Review::create([
        'product_id' => $product->id, 'user_id' => $users[0]->id,
        'rating' => 4, 'comment' => 'x', 'status' => ReviewStatus::Approved,
    ]);

    $product->refresh();
    expect((float) $product->avg_rating)->toBe(4.0)->and($product->reviews_count)->toBe(1);

    $review->update(['status' => ReviewStatus::Rejected]);
    $product->refresh();
    expect((float) $product->avg_rating)->toBe(0.0)->and($product->reviews_count)->toBe(0);

    $pending = Review::create([
        'product_id' => $product->id, 'user_id' => $users[1]->id,
        'rating' => 2, 'comment' => 'x', 'status' => ReviewStatus::Pending,
    ]);
    $product->refresh();
    expect($product->reviews_count)->toBe(0);

    $pending->update(['status' => ReviewStatus::Approved]);
    $product->refresh();
    expect((float) $product->avg_rating)->toBe(2.0)->and($product->reviews_count)->toBe(1);
});
