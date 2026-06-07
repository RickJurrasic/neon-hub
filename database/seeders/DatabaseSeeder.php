<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 🌌 Vytvoříme hlavního Demo rekrutera
        User::updateOrCreate(
            [
                'name' => 'Recruiter Phantom',
                'email' => 'demo@neonhub.io',
                'handle' => '@recruiter_alpha',
                'role' => 'CORPO_ELITE', //
                'bio' => 'Hlavní rekruter pro korporátní elitu. Vyhledává subjekty s vysokou latencí a čistým zdrojovým kódem.',
                'trust_level' => 75,
                'latency' => '18ms_STABLE',
                'password' => Hash::make('password'), // Nebo tvoje heslo
            ]
        );

        // 🤖 Inicializace systémových entit (AI agentů)
        $this->call([
            BotSeeder::class,
            PostSeeder::class,
        ]);
    }
}
