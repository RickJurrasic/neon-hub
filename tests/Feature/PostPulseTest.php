<?php

use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

beforeEach(function () {
    // Vytvoříme uživatele a přihlásíme ho
    $this->user = User::factory()->create();
});

test('neon hub index returns posts with standard integer IDs', function () {
    // Vytvoříme testovací post spojený s autorem
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
    ]);

    actingAs($this->user)
        ->get('/') // Doplň routu tvého indexu, pokud se liší
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('initialState.posts.0', fn ($postProp) => $postProp
                ->where('id', $post->id) // Ověříme, že ID je čistý integer (např. 1) a ne '0x1'
                ->etc()
            )
        );
});

test('authenticated user can pulse a post using integer id', function () {
    $post = Post::factory()->create();

    actingAs($this->user)
        ->post("/posts/{$post->id}/like") // Tvoje Axios URL z frontendu
        ->assertOk(); // Případně ->assertStatus(201) podle tvého controlleru

    // Ověříme, že se lajk reálně uložil do DB
    assertDatabaseHas('likes', [
        'user_id' => $this->user->id,
        'post_id' => $post->id,
    ]);
});
