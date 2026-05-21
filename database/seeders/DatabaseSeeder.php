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
        // 🌌 Vytvoříme hlavního Demo rekrutera, kterého middleware automaticky přihlásí
        User::create([
            'name' => 'Recruiter Phantom',
            'email' => 'demo@neonhub.io',
            'handle' => '@recruiter_alpha',
            'faction' => 'CORPO_ELITE',
            'password' => Hash::make('neon-secret-password-2026'), // Heslo je fuk, auth je natvrdo
        ]);

        // Sem pak časem můžeme přisypat ty další entity (Elysia V, Vector Prime atd.)
    }
}
