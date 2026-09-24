<?php

namespace App\Http\Middleware;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    #[\Override]
    public function share(Request $request): array
    {
        $user = $request->user();
        $userId = $user?->id;

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'handle' => $user->handle,
                    'faction' => $user->faction,
                    'status_text' => $user->status_text,
                    'avatar_url' => $user->avatar_url,
                    'bio' => $user->bio,
                ] : null,
            ],
            // Flash zprávy ze session
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Základní data pro NeonHub systém, která přežijí refresh
            'messages' => $user
                ? DB::table('agent_conversation_messages')->where('user_id', $user->id)->orderBy('created_at', 'asc')->get()
                : [],
            'posts' => Post::where(function ($query) use ($userId): void {
                $query->where('demo_owner_id', $userId)
                    ->orWhereNull('demo_owner_id');
            })
                ->latest()
                ->get(),
        ]);
    }
}
