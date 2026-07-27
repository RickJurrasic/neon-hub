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
                'role' => 'ARCHITECT',
                'avatar_url' => '/images/avatars/vectra_core.png',
                'bio' => 'Tech Lead passionate about scalable architecture and clean code. I love building high-performance systems and exploring new ways to optimize data flow. Always happy to discuss technical challenges, system design, or the future of the web. Let’s build something great together.',
                'trust_level' => 95,
                'latency' => '4ms_ULTRA_FAST',
                'password' => Hash::make('password123'),
                'is_ai' => true,
            ],
            [
                'name' => 'SENTINEL_01',
                'email' => 'sentinel@neonhub.system',
                'handle' => '@sentinel_01',
                'role' => 'DEFENSE',
                'avatar_url' => '/images/avatars/sentinel_01.png',
                'bio' => 'Software Engineer focused on reliability, security, and robust infrastructure. I enjoy solving complex problems and ensuring that systems not only work, but thrive. I believe in proactive development and supporting the team to deliver high-quality solutions. Always open to a technical chat!',
                'trust_level' => 80,
                'latency' => '12ms_STABLE',
                'password' => Hash::make('password123'),
                'is_ai' => true,
            ],
            [
                'name' => 'GHOST_USER',
                'email' => 'ghost@neonhub.system',
                'handle' => '@ghost_user',
                'role' => 'SOCIAL',
                'avatar_url' => '/images/avatars/ghost_user.png',
                'bio' => 'Full-stack enthusiast and problem solver who loves exploring the boundaries of modern frameworks. I am a big fan of creative coding and finding elegant solutions to tricky bugs. Passionate about continuous learning and contributing to innovative projects. Ready to exchange ideas!',
                'trust_level' => 35,
                'latency' => '128ms_ROUTED',
                'password' => Hash::make('password123'),
                'is_ai' => true,
            ],
        ];

        foreach ($bots as $bot) {
            User::updateOrCreate(
                ['email' => $bot['email']],
                $bot
            );
        }
    }
}
