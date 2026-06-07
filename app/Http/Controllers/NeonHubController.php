<?php

namespace App\Http\Controllers;

use App\Jobs\HandleAgentResponse;
use App\Models\Friendship; // Nový sjednocený job
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class NeonHubController extends Controller
{
    public function index()
    {
        $authId = auth()->id();

        $props = [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'initialState' => null,
        ];

        if ($authId) {
            $props['initialState'] = [
                'friendships' => $this->getFriendshipData($authId),
                'messages' => [], // Tvůj stávající prázdný array pro zprávy

                // TADY TO PŘIPOJÍME: Načteme posty i s autory a komentáři
                'posts' => Post::with(['author', 'comments.author'])
                    ->latest()
                    ->get()
                    ->map(function ($post) {
                        return [
                            'id' => '0x'.dechex($post->id), // Tvůj hexadecimální formát logů
                            'author' => $post->author->name ?? 'UNKNOWN_NODE',
                            'content' => $post->content,
                            'type' => $post->type,
                            'time' => $post->latency ?? '0.0ms', // Mapujeme DB latency na Vue 'time'
                            'likes_count' => $post->likes_count,
                            'comments_count' => $post->comments->count(),
                            'image' => $post->image_url,
                            'image_meta' => $post->image_meta,
                            'comments' => $post->comments->map(function ($comment) {
                                return [
                                    'id' => $comment->id,
                                    'author' => $comment->author->name ?? 'ANONYMOUS',
                                    'text' => $comment->content,
                                    'timestamp' => $comment->created_at->format('H:i'),
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
            ];

            // FIX: Voláme nový job. Pokud uživatel dá refresh, stavová pojistka uvnitř jobu
            // zabrání tomu, aby bot poslal zprávu podruhé.
            HandleAgentResponse::dispatch($authId)->delay(now()->addSeconds(7));
        }

        return Inertia::render('Welcome', $props);
    }

    private function getFriendshipData($authId): array
    {
        // 1. ŽÁDOSTI O PROPOJENÍ (Čekající žádosti, kde přihlášený uživatel je příjemcem)
        $requests = Friendship::where('recipient_id', $authId) // Hledáme tebe jako příjemce
            ->where('status', 'pending')
            ->join('users', 'friendships.sender_id', '=', 'users.id') // Join přes odesílatele žádosti
            ->select(
                'friendships.id',                // ID relace pro axios.patch/delete
                'users.id as user_id',           // ID uživatele pro Vue computed vyhledávání
                'users.name',
                'users.role',
                'users.bio',
                'users.trust_level',
                'users.latency',
                'users.avatar_url as avatar',   // Přemapování pro Vue img tag
                'friendships.status'
            )
            ->get()
            ->toArray();

        // 2. AKTIVNÍ SPOJENÍ (Akceptované linky, kde figuruješ jako odesílatel nebo příjemce)
        $active = Friendship::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->orWhere('recipient_id', $authId);
        })
            ->where('status', 'accepted')
            ->get()
            ->map(function ($friendship) use ($authId) {
                // Zjistíme, které ID patří tomu druhému (příteli)
                $friendId = $friendship->sender_id == $authId ? $friendship->recipient_id : $friendship->sender_id;
                $friend = User::find($friendId);

                if (! $friend) {
                    return null;
                }

                return [
                    'id' => $friendship->id,             // ID relace pro UNLINK (axios.delete)
                    'user_id' => $friend->id,            // ID uživatele pro Vue profil
                    'name' => $friend->name,
                    'role' => $friend->role ?? 'EXTERNAL_NODE',
                    'bio' => $friend->bio ?? '"Šifrované bio prázdné."',
                    'trust_level' => $friend->trust_level ?? 50,
                    'latency' => $friend->latency ?? '24ms_STABLE',
                    'avatar' => $friend->avatar_url,
                    'status' => 'accepted',
                ];
            })
            ->filter() // Vyčistíme případné null hodnoty
            ->values()
            ->toArray();

        return [
            'requests' => $requests,
            'active' => $active,
        ];
    }
}
