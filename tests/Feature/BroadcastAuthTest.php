<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Config;
use Tests\Support\FakeAuthBroadcaster;

/*
 * Authorization tests for the /broadcasting/auth route, proving the gate
 * registered in routes/channels.php (`App.Models.User.{userId}`: owner-only) is
 * honored over HTTP.
 *
 * The test env is forced to BROADCAST_CONNECTION=null (phpunit.xml). Under null,
 * NullBroadcaster::auth() is an empty no-op that ALWAYS authorizes, so the route
 * could never return 403 -- making denial assertions impossible. beforeEach()
 * therefore swaps in FakeAuthBroadcaster, which evaluates the REAL channels.php
 * gates via the framework's own verifyUserCanAccessChannel(), and re-requires
 * routes/channels.php so the real owner-gate is registered onto that broadcaster.
 */
beforeEach(function () {
        Config::set('broadcasting.default', 'auth_gate');
    Config::set('broadcasting.connections.auth_gate', ['driver' => 'auth_gate']);
    Broadcast::extend('auth_gate', fn () => new FakeAuthBroadcaster());
    Broadcast::forgetDrivers();                       // drop the cached null driver
    require base_path('routes/channels.php');         // re-register the REAL gates onto the fake
});

it('allows an authenticated user to authorize their own private user channel', function () {
    $user = makeDemoUser();

    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'channels'  => ['private-App.Models.User.'.$user->id],
            'socket_id' => '127.0.0.1.123.1',
        ])
        ->assertSuccessful();
});

it('denies an authenticated user access to another users private channel', function () {
    $owner = makeDemoUser();
    $other = makeDemoUser();

    $this->actingAs($other)
        ->post('/broadcasting/auth', [
            'channels'  => ['private-App.Models.User.'.$owner->id],
            'socket_id' => '127.0.0.1.123.2',
        ])
        ->assertForbidden();
});

it('lets a demo session authorize its own channel but not another demo sessions channel', function () {
    $sessionA = makeDemoUser();
    $sessionB = makeDemoUser();

    // Two distinct demo sessions (mirrors AutoLoginDemoUser per-browser minting).
    expect($sessionA->id)->not->toBe($sessionB->id)
        ->and($sessionA->is_ai)->toBeFalse()
        ->and($sessionB->is_ai)->toBeFalse();

    // Demo session A cannot subscribe to demo session B's private channel.
    $this->actingAs($sessionA)
        ->post('/broadcasting/auth', [
            'channels'  => ['private-App.Models.User.'.$sessionB->id],
            'socket_id' => '127.0.0.1.123.3',
        ])
        ->assertForbidden();

    // Demo session A's OWN channel still authorizes (positive control).
    $this->actingAs($sessionA)
        ->post('/broadcasting/auth', [
            'channels'  => ['private-App.Models.User.'.$sessionA->id],
            'socket_id' => '127.0.0.1.123.3',
        ])
        ->assertSuccessful();
});

it('denies an unauthenticated request for any private channel', function () {
    $user = makeDemoUser();

    $this->post('/broadcasting/auth', [
        'channels'  => ['private-App.Models.User.'.$user->id],
        'socket_id' => '127.0.0.1.123.4',
    ])->assertForbidden();
});

it('rejects authorization for an unregistered private channel (negative control)', function () {
    $user = makeDemoUser();

    // No Broadcast::channel() is registered for this name, so the framework's
    // verifyUserCanAccessChannel() finds no matching gate and denies (403).
    // This proves /broadcasting/auth actually enforces the channels.php gates
    // rather than no-op authorizing (as NullBroadcaster would under BROADCAST_CONNECTION=null).
    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'channels'  => ['private-App.Models.Nonexistent.'.$user->id],
            'socket_id' => '127.0.0.1.123.5',
        ])
        ->assertForbidden();
});
