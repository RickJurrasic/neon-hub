<?php

namespace App\Http\Controllers;

use App\Jobs\HandleAgentResponse;
use App\Services\NeonHubService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Response;
use Inertia\Inertia;

class NeonHubController extends Controller
{
    public function __construct(
        protected NeonHubService $neonHubService
    ) {}

    public function index(): Response
    {
        $authId = auth()->id();

        $props = [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'initialState' => $authId ? $this->neonHubService->getInitialState($authId) : null,
        ];

        if ($authId) {
            HandleAgentResponse::dispatch($authId)->delay(now()->addSeconds(7));
        }

        return Inertia::render('Welcome', $props);
    }
}