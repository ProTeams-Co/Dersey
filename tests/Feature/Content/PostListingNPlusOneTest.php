<?php

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTranslation;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createBlogPosts(int $count, PostCategory $category, Tag $tag): void
{
    foreach (range(1, $count) as $i) {
        $post = Post::create([
            'post_category_id' => $category->id,
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        PostTranslation::create([
            'post_id' => $post->id,
            'locale' => 'ar',
            'title' => "مقال {$i}",
            'content' => 'محتوى تجريبي',
        ]);

        $post->tags()->attach($tag->id);
    }
}

it('keeps the query count fixed when loading posts with translations, category, and tags, regardless of row count', function () {
    $category = PostCategory::create();
    $tag = Tag::create();

    createBlogPosts(5, $category, $tag);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $posts = Post::withCurrentTranslation('ar')->with(['category', 'tags'])->get();
    foreach ($posts as $post) {
        $post->translate('ar');
        $post->category;
        $post->tags;
    }

    $queriesForFiveRows = count(DB::getQueryLog());
    DB::disableQueryLog();

    createBlogPosts(20, $category, $tag); // 25 rows total now

    DB::flushQueryLog();
    DB::enableQueryLog();

    $posts = Post::withCurrentTranslation('ar')->with(['category', 'tags'])->get();
    foreach ($posts as $post) {
        $post->translate('ar');
        $post->category;
        $post->tags;
    }

    $queriesForTwentyFiveRows = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesForFiveRows)->toBe($queriesForTwentyFiveRows);
});
