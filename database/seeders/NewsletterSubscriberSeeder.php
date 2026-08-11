<?php

namespace Database\Seeders;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsletterSubscriberSeeder extends Seeder
{
    public function run(): void
    {
        if (NewsletterSubscriber::query()->exists()) {
            return;
        }

        foreach (range(1, 10) as $i) {
            $unsubscribed = $i % 4 === 0;

            NewsletterSubscriber::create([
                'email' => "subscriber{$i}@example.com",
                'is_active' => ! $unsubscribed,
                'subscribed_at' => now()->subDays(random_int(5, 200)),
                'unsubscribed_at' => $unsubscribed ? now()->subDays(random_int(1, 4)) : null,
                'token' => Str::random(40),
            ]);
        }
    }
}
