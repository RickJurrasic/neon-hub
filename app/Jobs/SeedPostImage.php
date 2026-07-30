<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SeedPostImage implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public readonly string $postType = 'AI_FEED'
    ) {}

    public function handle(): string
    {
        return self::generate();
    }

    /**
     * Statická metoda pro přímé volání bez zařazování do fronty.
     */
    public static function generate(): string
    {
        return 'https://picsum.photos/800/600?' . Str::random(10);
    }
}