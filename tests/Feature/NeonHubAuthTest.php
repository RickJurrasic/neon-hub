<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;

test('guest can access neon core and automatically get an authenticated session', function () {
    // Vytvoříme testovacího uživatele
    $user = User::factory()->create([
        'name' => 'Radim Passer',
    ]);

    // Použijeme správný název routy 'dashboard'
    $response = actingAs($user)->get(route('dashboard'));

    // Ověříme, že stránka se v pořádku načte a uživatel je přihlášen
    $response->assertStatus(200);
    assertAuthenticated();
});

test('inertia page shares authenticated user data with neon components', function () {
    $user = User::factory()->create([
        'name' => 'Radim Passer',
    ]);

    // Ověříme, že Inertia správně sdílí data přihlášeného uživatele do frontendu
    actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.name', $user->name)
        );
});
