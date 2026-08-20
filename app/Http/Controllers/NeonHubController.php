<?php

namespace App\Http\Controllers;

use App\Jobs\HandleAgentResponse;
use App\Services\NeonHubService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class NeonHubController extends Controller
{
    public function __construct(
        protected NeonHubService $neonHubService
    ) {}

    public function index(): Response
    {
        // Převedeme na int hned, aby PHPStan/Larastan přestal prskat na typové nesoulady
        $authId = auth()->id() ? (int) auth()->id() : null;

        $props = [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'initialState' => $authId ? $this->neonHubService->getInitialState($authId) : null,
        ];

        if ($authId) {
            HandleAgentResponse::dispatch($authId)->delay(now()->addSeconds(7));
        }

        return Inertia::render('Welcome', $props);
    }
}
