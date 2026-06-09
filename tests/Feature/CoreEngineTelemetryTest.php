<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Tento blok se spustí před každým testem v tomto souboru
beforeEach(function () {
    // Vytvoříme testovacího uživatele pomocí factory
    $this->user = User::factory()->create();
});

it('returns a successful telemetry response with correct structure', function () {
    // Act: Přihlásíme uživatele pomocí actingAs() před odesláním requestu
    $response = $this->actingAs($this->user)->getJson('/api/core-engine/telemetry');

    // Assert
    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'version',
            'activity_stream',
            'metrics' => [
                'memory_usage_mb',
                'pending_jobs',
                'slow_queries_detected',
                'slow_requests_detected',
                'db_connection',
            ],
        ]);

    $response->assertJson(['status' => 'ONLINE']);
});

it('includes pending jobs count in metrics as an integer', function () {
    // Act: Opět posíláme požadavek jako přihlášený uživatel
    $response = $this->actingAs($this->user)->getJson('/api/core-engine/telemetry');

    // Assert
    $response->assertStatus(200);

    $pendingJobs = $response->json('metrics.pending_jobs');

    expect($pendingJobs)->toBeInt()
        ->toBeGreaterThanOrEqual(0);
});
