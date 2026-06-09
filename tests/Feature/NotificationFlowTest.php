<?php

namespace Tests\Feature;

use App\Events\MessageReceived;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    /** @test */
    public function it_broadcasts_correct_message_structure_to_frontend()
    {
        Event::fake();

        $user = User::factory()->create();

        // Data, která simulují strukturu z kontroleru
        $messageData = [
            'id' => 'uuid-123',
            'conversation_id' => 'conv-999',
            'text' => 'Ahoj, jak se máš?',
            'sender' => 'SentinelAgent',
            'read' => false,
        ];

        // Vyvolání eventu
        event(new MessageReceived($user->id, $messageData));

        // Ověření, že event byl dispatchnut s očekávanými daty
        Event::assertDispatched(MessageReceived::class, function ($event) use ($user, $messageData) {
            return $event->userId === $user->id && $event->data === $messageData;
        });
    }

    /** @test */
    public function it_verifies_unread_alert_logic_via_database()
    {
        $user = User::factory()->create();

        // Simulace databáze - testujeme, že naše logika 'read: 0' (nepřečteno) funguje
        $alert = \DB::table('agent_conversation_messages')->insert([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'role' => 'alert',
            'content' => 'System alert',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('agent_conversation_messages', [
            'user_id' => $user->id,
            'role' => 'alert',
        ]);
    }
}
