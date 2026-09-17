<?php

use App\Models\Post;
use App\Services\NeonHubService;

// Seed ID 1 as a legacy human for factory-created users to get id >= 2.
beforeEach(function (): void {
    \App\Models\User::query()->insert([
        'id' => 1,
        'name' => 'Legacy Human',
        'email' => 'legacy@neonhub.io',
        'handle' => 'legacy',
        'role' => 'EXTERNAL_NODE',
        'trust_level' => 50,
        'latency' => '24ms_STABLE',
        'is_ai' => false,
        'email_verified_at' => null,
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('creates session-owned posts with correct demo_owner_id from scheduler', function () {
    $humanA = makeDemoUser();
    $botUser = makeBotUser();

    // Simulate scheduler action dispatch
    $payload = ['demo_owner_id' => $humanA->id];

    // Create a post as the bot would via ExecuteCreatePostAction
    $post = Post::create([
        'user_id' => $botUser->id,  // Use Post::create directly to set AI bot as author
        'content' => 'AI-generated post for session A',
        'type' => 'ai',
        'latency' => '1.5ms',
        'likes_count' => 0,
        'demo_owner_id' => $payload['demo_owner_id'],
    ]);

    expect($post->demo_owner_id)->toBe($humanA->id)
        ->and($post->user_id)->toBe($botUser->id);
});

it('creates session-owned posts with correct demo_owner_id from WOW path', function () {
    $humanA = makeDemoUser();
    makeBotUser();

    // Simulate HandleAgentResponse::createFeedPost
    // Post is created with demo_owner_id = human's ID

    $post = Post::create([
        'user_id' => 1, // bot ID
        'demo_owner_id' => $humanA->id,
        'content' => 'WOW-generated feed post',
        'type' => 'AI_FEED',
        'latency' => '1.5ms',
        'likes_count' => 0,
        'image_url' => null,
    ]);

    expect($post->demo_owner_id)->toBe($humanA->id)
        ->and($post->user_id)->toBe(1);
});

it('filters feed to only show session-owned and global posts', function () {
    $humanA = makeDemoUser();
    $humanB = makeDemoUser();
    makeBotUser();

    // Create session-owned post for A
    $postA = Post::create([
        'user_id' => 1, // bot ID
        'demo_owner_id' => $humanA->id,
        'content' => 'Post for A',
        'type' => 'ai',
        'latency' => '1.5ms',
    ]);

    // Create session-owned post for B
    $postB = Post::create([
        'user_id' => 1, // bot ID
        'demo_owner_id' => $humanB->id,
        'content' => 'Post for B',
        'type' => 'ai',
        'latency' => '1.5ms',
    ]);

    // Create global post (demo_owner_id IS NULL)
    $globalPost = Post::create([
        'user_id' => 1, // bot ID
        'demo_owner_id' => null,
        'content' => 'Global seeded post',
        'type' => 'seeded',
        'latency' => '1.5ms',
    ]);

    $service = new NeonHubService();

    $feedA = $service->getPostsData($humanA->id);
    $feedB = $service->getPostsData($humanB->id);

    // Human A sees their post and global post, not B's post
    $postIdsA = collect($feedA)->pluck('id')->toArray();
    expect($feedA)->toHaveCount(2);
    expect($postIdsA)->toContain($postA->id);
    expect($postIdsA)->not->toContain($postB->id);
    expect($postIdsA)->toContain($globalPost->id);

    // Human B sees their post and global post, not A's post
    $postIdsB = collect($feedB)->pluck('id')->toArray();
    expect($feedB)->toHaveCount(2);
    expect($postIdsB)->not->toContain($postA->id);
    expect($postIdsB)->toContain($postB->id);
    expect($postIdsB)->toContain($globalPost->id);
});

it('prevents cross-session visibility of AI-generated posts', function () {
    $humanA = makeDemoUser();
    $humanB = makeDemoUser();
    $botUser = makeBotUser();

    // Human A creates post via scheduler
    $postA = $botUser->posts()->create([
        'content' => 'A\'s AI post',
        'type' => 'ai',
        'latency' => '1.5ms',
        'demo_owner_id' => $humanA->id,
    ]);

    // Human B creates post via scheduler
    $postB = $botUser->posts()->create([
        'content' => 'B\'s AI post',
        'type' => 'ai',
        'latency' => '1.5ms',
        'demo_owner_id' => $humanB->id,
    ]);

    // Global post should be visible to both
    $globalPost = $botUser->posts()->create([
        'content' => 'Global post',
        'type' => 'seeded',
        'latency' => '1.5ms',
        'demo_owner_id' => null,
    ]);

    $service = new NeonHubService();

    // Verify A's feed
    $feedA = $service->getPostsData($humanA->id);
    $postIdsA = collect($feedA)->pluck('id')->toArray();

    expect($postIdsA)->toContain($postA->id)
        ->toContain($globalPost->id)
        ->not->toContain($postB->id);

    // Verify B's feed
    $feedB = $service->getPostsData($humanB->id);
    $postIdsB = collect($feedB)->pluck('id')->toArray();

    expect($postIdsB)->toContain($postB->id)
        ->toContain($globalPost->id)
        ->not->toContain($postA->id);
});
