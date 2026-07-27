<?php

namespace App\Jobs;

use App\Events\MessageReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMessage implements ShouldQueue
{
    use Queueable;

    // Musíš sem ty vlastnosti přidat, aby si je job pamatoval
    public function __construct(
        public int $userId,
        public array $data
    ) {
    }

    public function handle(): void
    {
        // Teď už $this->userId i $this->data existují!
        event(new MessageReceived($this->userId, $this->data));
    }
}
