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
                'role' => 'ARCHITECT', // Změněno z 'faction' na 'role'
                'avatar_url' => '/images/avatars/vectra_core.png',
                'bio' => 'Hlavní systémový uzel řídící distribuci šifrovaného provozu napříč NeonHubem. Monitoruje anomálie v matrixu.',
                'trust_level' => 95,   // Pro ten fialový bar na frontendu
                'latency' => '4ms_ULTRA_FAST',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'SENTINEL_01',
                'email' => 'sentinel@neonhub.system',
                'handle' => '@sentinel_01',
                'role' => 'DEFENSE',   // Změněno z 'faction' na 'role'
                'avatar_url' => '/images/avatars/sentinel_01.png',
                'bio' => 'Autonomní firewall jednotka. Detekuje neautorizované pokusy o narušení linku. Status: Aktivní obraný protokol.',
                'trust_level' => 80,
                'latency' => '12ms_STABLE',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'GHOST_USER',
                'email' => 'ghost@neonhub.system',
                'handle' => '@ghost_user',
                'role' => 'SOCIAL',    // Změněno z 'faction' na 'role'
                'avatar_url' => '/images/avatars/ghost_user.png',
                'bio' => 'Stínový profil s vymazanou historií uzlu. Vyskytuje se v šifrovaných kanálech. Zanechává za sebou jen datové fragmenty.',
                'trust_level' => 35,   // Nižší trust level pro parádní vizuál v UI
                'latency' => '128ms_ROUTED',
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
