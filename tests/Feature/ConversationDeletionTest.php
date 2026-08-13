<?php

use App\Actions\SendMessageAction;
use App\Events\MessageReceived;
use App\Jobs\HandleAgentResponse;
use App\Models\User;
use App\Services\MessageService;
use App\Services\NeonHubService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('writes a human-owned conversation with the bot recorded on agent_user_id', function (): void {
    Event::fake([MessageReceived::class]);

    $human = User::factory()->create();
    $bot = User::factory()->create(['name' => 'SENTINEL_01', 'is_ai' => true]);

    app(SendMessageAction::class)->execute(
        $bot->id,
        $human->id,
        'Connection established.',
        'SENTINEL_01',
        'assistant'
    );

    $conversation = DB::table('agent_conversations')
        ->where('agent_user_id', $bot->id)
        ->first();

    expect($conversation)->not->toBeNull()
        ->and($conversation->user_id)->toBe($human->id)
        ->and($conversation->agent_user_id)->toBe($bot->id);

    $this->assertDatabaseHas('agent_conversation_messages', [
        'conversation_id' => $conversation->id,
        'user_id' => $human->id,
        'role' => 'assistant',
    ]);
});

it('allows an owner to delete their own conversation and purges child messages', function (): void {
    $human = User::factory()->create();
    $bot = User::factory()->create(['name' => 'SENTINEL_01', 'is_ai' => true]);

    $conversationId = (string) Str::uuid();
    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $human->id,
        'agent_user_id' => $bot->id,
        'title' => 'SECURE_CHANNEL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('agent_conversation_messages')->insert([
        'id' => (string) Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $human->id,
        'agent' => 'App\\Ai\\Agents\\SentinelAgent',
        'role' => 'assistant',
        'content' => 'Hello, human.',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '[]',
        'meta' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($human)
        ->delete('/conversations/'.$conversationId)
        ->assertOk()
        ->assertJson(['status' => 'NODE_PURGED']);

    $this->assertDatabaseMissing('agent_conversations', ['id' => $conversationId]);
    $this->assertDatabaseMissing('agent_conversation_messages', ['conversation_id' => $conversationId]);
});

it('forbids a non-owner from deleting someone elses conversation', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $bot = User::factory()->create(['name' => 'SENTINEL_01', 'is_ai' => true]);

    $conversationId = (string) Str::uuid();
    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $owner->id,
        'agent_user_id' => $bot->id,
        'title' => 'SECURE_CHANNEL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($attacker)
        ->delete('/conversations/'.$conversationId)
        ->assertForbidden();

    $this->assertDatabaseHas('agent_conversations', ['id' => $conversationId, 'user_id' => $owner->id]);
});

it('service layer refuses to delete a conversation owned by someone else', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $conversationId = (string) Str::uuid();
    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $owner->id,
        'agent_user_id' => null,
        'title' => 'SECURE_CHANNEL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($attacker);

    try {
        app(MessageService::class)->destroyConversation($conversationId);
        $this->fail('Expected AuthorizationException was not thrown.');
    } catch (AuthorizationException $e) {
        expect(true)->toBeTrue();
    }

    $this->assertDatabaseHas('agent_conversations', ['id' => $conversationId]);
});

it('resolves the bot identity for legacy conversations with a null agent_user_id', function (): void {
    $human = User::factory()->create();
    $bot = User::factory()->create(['name' => 'SENTINEL_01', 'is_ai' => true]);

    $conversationId = (string) Str::uuid();
    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $bot->id,
        'agent_user_id' => null,
        'title' => 'SYSTEM_GREETING',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('agent_conversation_messages')->insert([
        'id' => (string) Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $human->id,
        'agent' => 'App\\Ai\\Agents\\SentinelAgent',
        'role' => 'assistant',
        'content' => 'Legacy hello.',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '[]',
        'meta' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // getAgentFromConversation falls back to user_id when agent_user_id is NULL.
    $job = new HandleAgentResponse($human->id, $conversationId, null);
    $method = new ReflectionMethod(HandleAgentResponse::class, 'getAgentFromConversation');
    $method->setAccessible(true);
    $resolved = $method->invoke($job, $conversationId);

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($bot->id);

    // NeonHubService must still resolve the bot name via COALESCE fallback.
    $messages = app(NeonHubService::class)->getMessagesData($human->id);
    $row = collect($messages)->firstWhere('conversation_id', $conversationId);

    expect($row)->not->toBeNull();
    expect($row['sender'])->toBe($bot->name);
});

it('migration exposes the agent_user_id column on agent_conversations', function (): void {
    expect(Schema::hasColumn('agent_conversations', 'agent_user_id'))->toBeTrue();
});