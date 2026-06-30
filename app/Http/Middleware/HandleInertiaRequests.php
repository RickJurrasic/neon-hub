<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'handle' => $request->user()->handle,
                    'faction' => $request->user()->faction,
                    'status_text' => $request->user()->status_text,
                    'avatar_url' => $request->user()->avatar_url,
                    'bio' => $request->user()->bio,
                ] : null,
            ],
        ]);
    }
}
