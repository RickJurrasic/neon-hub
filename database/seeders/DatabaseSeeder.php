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
            ['email' => 'demo@neonhub.io'],
            [
                'name' => 'Recruiter Phantom',
                'handle' => '@recruiter_alpha',
                'faction' => 'CORPO_ELITE',
                'password' => Hash::make('neon-secret-password-2026'),
            ]
        );

        // 🤖 Inicializace systémových entit (AI agentů)
        $this->call([
            BotSeeder::class,
        ]);
    }
}
