<?php

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewHelpfulVote;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows only one helpful vote per user per review, enforced at the database level', function () {
    $product = Product::factory()->create();
    $author = User::factory()->create();
    $voter = User::factory()->create();

    $review = Review::create([
        'product_id' => $product->id, 'user_id' => $author->id,
        'rating' => 5, 'comment' => 'x', 'status' => ReviewStatus::Approved,
    ]);

    ReviewHelpfulVote::create(['review_id' => $review->id, 'user_id' => $voter->id]);

    expect(fn () => ReviewHelpfulVote::create(['review_id' => $review->id, 'user_id' => $voter->id]))
        ->toThrow(QueryException::class);

    expect($review->helpfulVotes()->count())->toBe(1)
        ->and($review->fresh()->helpful_count)->toBe(1);
});

it('recalculates helpful_count exactly as votes are added and removed', function () {
    $product = Product::factory()->create();
    $author = User::factory()->create();
    $voters = User::factory()->count(3)->create();

    $review = Review::create([
        'product_id' => $product->id, 'user_id' => $author->id,
        'rating' => 5, 'comment' => 'x', 'status' => ReviewStatus::Approved,
    ]);

    $votes = $voters->map(fn (User $voter) => ReviewHelpfulVote::create([
        'review_id' => $review->id, 'user_id' => $voter->id,
    ]));

    expect($review->fresh()->helpful_count)->toBe(3);

    $votes->first()->delete();

    expect($review->fresh()->helpful_count)->toBe(2);
});
