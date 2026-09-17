<?php

use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;

/*
 * Ownership/authorization regression suite for the security-readiness
 * hardening pass. These tests assert the EXISTING guards — they make no
 * change to policies, controllers, services, or routes.
 */

test('non-owner cannot accept someone elses friendship', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $outsider = User::factory()->create();

    $friendship = Friendship::create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'status' => 'pending',
    ]);

    // Outsider is neither sender nor recipient -> FriendshipPolicy::update denies.
    $this->actingAs($outsider)
        ->patchJson(route('friendships.update', $friendship))
        ->assertStatus(403);

    $this->assertDatabaseHas('friendships', [
        'id' => $friendship->id,
        'status' => 'pending',
    ]);
});

test('non-owner cannot delete someone elses friendship', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $outsider = User::factory()->create();

    $friendship = Friendship::create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'status' => 'pending',
    ]);

    // Outsider is neither sender nor recipient -> FriendshipPolicy::delete denies.
    $this->actingAs($outsider)
        ->deleteJson(route('friendships.destroy', $friendship))
        ->assertStatus(403);

    $this->assertDatabaseHas('friendships', [
        'id' => $friendship->id,
        'status' => 'pending',
    ]);
});

test('recipient can accept their own friendship', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $friendship = Friendship::create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'status' => 'pending',
    ]);

    $this->actingAs($recipient)
        ->patchJson(route('friendships.update', $friendship))
        ->assertStatus(200)
        ->assertJson(['status' => 'success']);

    $this->assertDatabaseHas('friendships', [
        'id' => $friendship->id,
        'status' => 'accepted',
    ]);
});

test('sender can delete their own friendship', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $friendship = Friendship::create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'status' => 'pending',
    ]);

    $this->actingAs($sender)
        ->deleteJson(route('friendships.destroy', $friendship))
        ->assertStatus(200);

    $this->assertDatabaseMissing('friendships', [
        'id' => $friendship->id,
    ]);
});

test('non-author cannot update another users comment', function (): void {
    $author = User::factory()->create();
    $attacker = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($author)
        ->postJson(route('comments.store', $post), [
            'content' => 'Original comment.',
        ])
        ->assertStatus(200);

    $comment = Comment::where('post_id', $post->id)
        ->where('user_id', $author->id)
        ->first();

    // UpdateCommentRequest::authorize() consults CommentPolicy::update, which
    // is author-only; denial happens before validation/mutation.
    $this->actingAs($attacker)
        ->patchJson(route('comments.update', $comment), [
            'content' => 'Hijacked comment.',
        ])
        ->assertStatus(403);

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'content' => 'Original comment.',
    ]);
});

test('author can update their own comment', function (): void {
    $author = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($author)
        ->postJson(route('comments.store', $post), [
            'content' => 'Original comment.',
        ])
        ->assertStatus(200);

    $comment = Comment::where('post_id', $post->id)
        ->where('user_id', $author->id)
        ->first();

    $this->actingAs($author)
        ->patchJson(route('comments.update', $comment), [
            'content' => 'Updated comment.',
        ])
        ->assertStatus(200)
        ->assertJson(['status' => 'NODE_UPDATED']);

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'content' => 'Updated comment.',
    ]);
});

test('user cannot remove another users like', function (): void {
    $post = Post::factory()->create();
    $liker = User::factory()->create();
    $attacker = User::factory()->create();

    // User A likes the post.
    $this->actingAs($liker)
        ->postJson(route('posts.like', $post))
        ->assertStatus(200);

    $this->assertDatabaseHas('likes', [
        'user_id' => $liker->id,
        'post_id' => $post->id,
    ]);

    // User B attempts to unlike A's post. Unlike is scoped to the authenticated
    // user, so this is a safe no-op: none of A likes are removed.
    $this->actingAs($attacker)
        ->deleteJson(route('posts.unlike', $post))
        ->assertStatus(200)
        ->assertJson([
            'is_liked' => false,
            'likes_count' => 1,
        ]);

    // A like remains intact.
    $this->assertDatabaseHas('likes', [
        'user_id' => $liker->id,
        'post_id' => $post->id,
    ]);

    $this->assertSame(1, $post->fresh()->likes()->count());
});
