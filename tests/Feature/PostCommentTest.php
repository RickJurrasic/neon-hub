<?php

use App\Models\Post;
use App\Models\User;

test('authenticated user can post a comment to a post', function () {
    // 1. Arrange: Vytvoříme uživatele a příspěvek
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $commentData = [
        'content' => 'Tohle je kybernetický komentář z testu.',
    ];

    // 2. Act: Pošleme request jako přihlášený uživatel
    $response = $this->actingAs($user)
        ->postJson(route('comments.store', $post), $commentData);

    // 3. Assert: Ověříme úspěch
    $response->assertStatus(200); // Nebo 201 dle tvé implementace

    // Ověříme, že komentář skutečně existuje v databázi
    $this->assertDatabaseHas('comments', [
        'user_id' => $user->id,
        'post_id' => $post->id,
        'content' => 'Tohle je kybernetický komentář z testu.',
    ]);
});

test('guest cannot post a comment', function () {
    $post = Post::factory()->create();

    $response = $this->postJson(route('comments.store', $post), [
        'content' => 'Hackerský pokus bez přihlášení.',
    ]);

    $response->assertStatus(401); // Neautorizovaný přístup
});
