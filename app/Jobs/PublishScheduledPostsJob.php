<?php

namespace App\Jobs;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Flips every due "scheduled" post to "published" - meant to run on a
 * schedule (e.g. every minute), not on-demand. No notifications are sent
 * (out of scope for this batch).
 */
class PublishScheduledPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Post::query()
            ->where('status', PostStatus::Scheduled)
            ->where('published_at', '<=', now())
            ->chunkById(100, function ($posts) {
                foreach ($posts as $post) {
                    $post->update(['status' => PostStatus::Published]);
                }
            });
    }
}
