<?php

namespace App\Jobs;

use App\Actions\SendFriendRequestAction;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendFriendRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $botName = 'SENTINEL_01'
    ) {}

    public function handle(SendFriendRequestAction $action): void
    {
        $bot = User::where('name', $this->botName)->firstOrFail();

        // Finální neprůstřelná kontrola idempotence přímo v asynchronním Jobu
        $exists = DB::table('friendships')
            ->where(function ($query) use ($bot) {
                $query->where('sender_id', $bot->id)
                    ->where('recipient_id', $this->userId);
            })
            ->orWhere(function ($query) use ($bot) {
                $query->where('sender_id', $this->userId)
                    ->where('recipient_id', $bot->id);
            })
            ->exists();

        // Pokud záznam v DB už existuje, job tiše a bezpečně ukončíme (nikam nic neduplikuje)
        if ($exists) {
            return;
        }

        $action->execute($bot->id, $this->userId);
    }
}
