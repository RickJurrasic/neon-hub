<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMessage implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Počet pokusů o opakování jobu při selhání.
     */
    public int $tries = 3;

    /**
     * Proleva mezi opakovanými pokusy (v sekundách).
     */
    public int $backoff = 5;

    /**
     * Maximální doba běhu jobu (v sekundách).
     */
    public int $timeout = 30;

    /**
     * @param int $userId
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $data
    ) {}

    public function handle(): void
    {
        event(new MessageReceived($this->userId, $this->data));

        Log::info("SendMessage: Event MessageReceived dispatched for User {$this->userId}.");
    }

    /**
     * Ošetření trvalého selhání jobu.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("SendMessage failed permanently for User {$this->userId}: {$exception->getMessage()}");
    }
}