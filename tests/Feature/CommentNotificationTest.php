<?php

use App\Events\NewActivityAlert;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('majitel příspěvku obdrží notifikaci, když cizí uživatel přidá komentář', function () {
    // 1. Fakeujeme eventy, aby se skutečně neposílaly přes Reverb
    Event::fake([NewActivityAlert::class]);

    // 2. Příprava: majitel postu a cizí komentující
    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    // 3. Akce: komentující vytvoří komentář
    $response = $this->actingAs($commenter)
        ->postJson(route('comments.store', $post->id), [
            'content' => 'Tohle je testovací komentář!',
        ]);

    // 4. Ověření: komentář v DB
    $response->assertStatus(200);
    $this->assertDatabaseHas('comments', [
        'post_id' => $post->id,
        'user_id' => $commenter->id,
    ]);

    // 5. Ověření: Event NewActivityAlert byl odeslán majiteli
    Event::assertDispatched(NewActivityAlert::class, function ($event) use ($owner) {
        return $event->userId === $owner->id;
    });
});

test('majitel příspěvku neobdrží notifikaci, pokud okomentuje svůj vlastní příspěvek', function () {
    Event::fake([NewActivityAlert::class]);

    $owner = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $owner->id]);

    // Majitel komentuje sám sobě
    $this->actingAs($owner)
        ->postJson(route('comments.store', $post->id), [
            'content' => 'Vlastní komentář.',
        ]);

    // Event by neměl být odeslán
    Event::assertNotDispatched(NewActivityAlert::class);
});
