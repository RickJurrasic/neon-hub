<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 🤖 Inicializace systémových entit (AI agentů)
        $this->call([
            BotSeeder::class,
            PostSeeder::class,
        ]);

        // 💬 Friendships se vytvářejí automaticky přes scheduler
        // Boti nejdříš pošlou žádost o přátelství a až poté mohou posílat zprávy
    }
}
