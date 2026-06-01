<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Application; // Potřebné pro Application::VERSION
use Illuminate\Support\Facades\Route;  // Potřebné pro Route::has()
use Inertia\Inertia;

class NeonHubController extends Controller
{
    public function index()
    {
        $authId = auth()->id();

        // 1. Sem jsme přestěhovali tvoje původní props z web.php
        $props = [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'initialState' => null, // Výchozí stav, pokud je uživatel host
        ];

        // 2. Pokud je uživatel přihlášený, nabalíme do props kompletní cyber-stav
        if ($authId) {
            $props['initialState'] = [
                'friendships' => $this->getFriendshipData($authId),
                // 'messages' => $this->getMessageData($authId),
                // 'alerts' => $this->getAlertData($authId), // Zatím vrací prázdné pole
            ];
        }

        // 3. Renderujeme stránku Welcome a předáme jí VŠECHNY props naráz
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

    // private function getMessageData($authId)
    // {
    //    // Vrátí dešifrovaný inbox pro NeonMessages
    //    return Message::where('receiver_id', $authId)
    //        ->join('users', 'messages.sender_id', '=', 'users.id')
    //        ->select('messages.id', 'users.name as sender', 'messages.text', 'messages.created_at as time', 'messages.read')
    //        ->orderBy('messages.created_at', 'desc')
    //        ->get();
    // }
    //
    // private function getAlertData($authId): array
    // {
    //    // Příprava pro budoucí NeonNotifications tabulku
    //    return [];
    // }
}
