<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SeedPostImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $postType;

    public function __construct(string $postType = 'AI_FEED')
    {
        $this->postType = $postType;
    }

    public function handle(): ?string
    {
        // Picsum photo API vrací náhodný obrávek
        // Používáme přímý URL k náhodnému obrávku velikosti 800x600
        return 'https://picsum.photos/800/600?'.Str::random(10);
    }

    /**
     * Statická metoda pro přímé volání bez queue
     */
    public static function generate(): string
    {
        return 'https://picsum.photos/800/600?'.Str::random(10);
    }
}
