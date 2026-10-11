<?php

use App\Models\User;
use App\Services\ActiveDemoUsers;
use Illuminate\Support\Facades\Cache;

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

test('anonymous homepage visit does not activate the AI scheduler before Enter System', function (): void {
    // Reserve ID 1 for the legacy global identity, as in production.
    User::factory()->create([
        'id' => 1,
        'name' => 'Legacy Recruiter',
        'email' => 'legacy-test@neonhub.io',
        'handle' => 'legacy',
        'is_ai' => false,
    ]);

    Cache::flush();

    // A visitor receives a demo identity, but has not entered the system.
    $this->get(route('neon.hub'))->assertOk();

    $demo = User::query()
        ->where('is_ai', false)
        ->where('handle', 'like', 'demo-%')
        ->firstOrFail();

    expect(Cache::has(
        ActiveDemoUsers::ACTIVE_KEY.':'.$demo->id
    ))->toBeFalse();
});
