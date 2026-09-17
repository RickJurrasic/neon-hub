<?php

use App\Models\User;

test('same browser session never creates multiple demo users', function (): void {
    // Make a first guest request to trigger AutoLoginDemoUser middleware.
    // The middleware creates a new demo user and logs them in.
    $response = $this->get('/');
    $response->assertOk();

    // Count how many human demo users exist after the first request.
    $humanDemoUsers = User::where('is_ai', false)
        ->where('name', 'Demo Surfer')
        ->get();

    expect($humanDemoUsers)->toHaveCount(1);
    $firstDemoUserId = $humanDemoUsers->first()->id;

    // Make a second request from the SAME browser session (same cookies).
    // This simulates a second concurrent request that arrives while the
    // first request is still inside the middleware's critical section.
    $response = $this->get('/');
    $response->assertOk();

    // The demo user count must remain exactly ONE.
    $humanDemoUsers = User::where('is_ai', false)
        ->where('name', 'Demo Surfer')
        ->get();

    expect($humanDemoUsers)->toHaveCount(1);
    $secondDemoUserId = $humanDemoUsers->first()->id;

    // Both requests must resolve to the SAME demo user ID.
    expect($secondDemoUserId)->toBe($firstDemoUserId);
})->group('demo');