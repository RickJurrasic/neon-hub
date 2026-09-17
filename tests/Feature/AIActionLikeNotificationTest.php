<?php

use App\Ai\Agents\Actions\Ai\ExecuteLikePostAction;
use App\Events\PostLiked;
use App\Models\Post;
use App\Models\User;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

test('AI action like on demo-owned post creates notification for demo owner, not AI actor', function (): void {
    // This tests the scenario where:
    // - An AI bot ($user) is the liker/executing user
    // - The post has demo_owner_id set to a human session owner
    // - The post.user_id is the AI bot
    //
    // This should create a database notification for the human demo owner,
    // NOT for the AI bot.

    Event::fake([PostLiked::class]);

    // Human session owner (receives notification)
    $humanOwner = User::factory()->create(['is_ai' => false]);
    $humanOwner->handle = 'demo_like_owner';
    $humanOwner->save();

    // AI actor (owns the post in DB, is the liker)
    $aiBot = User::factory()->create(['is_ai' => true]);
    $aiBot->name = 'POLICY_BOT_LIKE';
    $aiBot->save();

    // Post owned by AI but with human as demo_owner
    $post = Post::factory()->create([
        'user_id' => $aiBot->id,           // Post owned by AI in DB
        'demo_owner_id' => $humanOwner->id, // But human is the "owner" for demo
    ]);

    // Simulate AI action: AI bot likes its own demo-owned post
    $payload = [
        'post_id' => $post->id,
        'demo_owner_id' => $humanOwner->id,
    ];

    $action = new ExecuteLikePostAction();
    $action->execute($aiBot, $payload);

    // 1. WebSocket event should target human owner's channel ✅
    Event::assertDispatched(PostLiked::class, function ($event) use ($humanOwner) {
        return $event->postOwnerId === $humanOwner->id;
    });

    // 2. DATABASE NOTIFICATION should be sent to human owner, NOT AI bot ✅
    assertDatabaseHas('notifications', [
        'notifiable_id' => $humanOwner->id,
        'notifiable_type' => User::class,
    ]);

    // 3. DATABASE NOTIFICATION should NOT be sent to AI bot
    assertDatabaseMissing('notifications', [
        'notifiable_id' => $aiBot->id,
        'notifiable_type' => User::class,
    ]);
});

test('AI action like on AI-owned post creates no notification (self-like)', function (): void {
    // When AI bot likes a post they own (no demo_owner_id), no notification should be created

    Event::fake([PostLiked::class]);

    $aiBot = User::factory()->create(['is_ai' => true]);
    $aiBot->name = 'SINGLE_BOT_LIKE';
    $aiBot->save();

    // Post owned and demo_owner_id by same AI (or null)
    $post = Post::factory()->create([
        'user_id' => $aiBot->id,
    ]);

    $payload = [
        'post_id' => $post->id,
    ];

    $action = new ExecuteLikePostAction();
    $action->execute($aiBot, $payload);

    // WebSocket event should target AI bot (postOwnerId = user_id = aiBot)
    Event::assertDispatched(PostLiked::class, function ($event) use ($aiBot) {
        return $event->postOwnerId === $aiBot->id;
    });

    // No notification should be created for self-like
    assertDatabaseMissing('notifications', [
        'notifiable_id' => $aiBot->id,
        'notifiable_type' => User::class,
    ]);
});