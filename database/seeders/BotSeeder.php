<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BotSeeder extends Seeder
{
    public function run(): void
    {
        $bots = [
            [
                'name' => 'VECTRA_CORE',
                'email' => 'vectra@neonhub.system',
                'handle' => '@vectra_core',
                'faction' => 'ARCHITECT',
                'avatar_url' => '/images/avatars/vectra_core.png', // Přidaná cesta
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'SENTINEL_01',
                'email' => 'sentinel@neonhub.system',
                'handle' => '@sentinel_01',
                'faction' => 'DEFENSE',
                'avatar_url' => '/images/avatars/sentinel_01.png', // Přidaná cesta
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'GHOST_USER',
                'email' => 'ghost@neonhub.system',
                'handle' => '@ghost_user',
                'faction' => 'SOCIAL',
                'avatar_url' => '/images/avatars/ghost_user.png', // Přidaná cesta
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($bots as $bot) {
            User::updateOrCreate(
                ['email' => $bot['email']], // Hledá podle emailu
                $bot                         // Pokud nenajde, vytvoří; pokud najde, aktualizuje
            );
        }
    }
}
