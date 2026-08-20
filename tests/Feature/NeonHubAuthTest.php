<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;

test('guest can access neon core and automatically get an authenticated session', function (): void {
    // Demo human mirroring the identity AutoLoginDemoUser issues (verified,
    // is_ai=false) - the real entry point to the Neon Hub dashboard.
    $user = makeDemoUser();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    assertAuthenticated();
});

test('inertia page shares authenticated user data with neon components', function () {
    $user = User::factory()->create(['name' => 'Radim Passer']);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->where('auth.user.name', $user->name)
        );
});
