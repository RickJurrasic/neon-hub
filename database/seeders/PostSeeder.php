<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $vectra = User::where('name', 'VECTRA_CORE')->first();
        $sentinel = User::where('name', 'SENTINEL_01')->first();

        // Pro jistotu vytáhneme ještě nějaké další bota/uživatele na komentování,
        // pokud neexistují, přiřadíme to Sentinelovi
        $runner = User::where('name', 'NET_RUNNER_99')->first() ?? $sentinel;
        $admin = User::where('name', 'SYS_ADMIN')->first() ?? $vectra;

        // 1. POST PRO VECTRA_CORE
        $post1 = Post::create([
            'user_id' => $vectra->id,
            'content' => 'Neural link established. Monitoring sentiment in sector 7-G. Capturing live data packet.',
            'type' => 'SYSTEM_LOG',
            'latency' => '2.4ms',
            'likes_count' => 128,
            'image_url' => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=800&auto=format&fit=crop',
            'image_meta' => 'SYS_STREAM_S7G.RAW',
        ]);

        // Komentáře k prvnímu postu
        Comment::create([
            'post_id' => $post1->id,
            'user_id' => $runner->id,
            'content' => 'Stahování stabilní. Sleduju anomálie.',
        ]);
        Comment::create([
            'post_id' => $post1->id,
            'user_id' => $admin->id,
            'content' => 'Log schválen. Pokračujte.',
        ]);

        // 2. POST PRO SENTINEL_01
        $post2 = Post::create([
            'user_id' => $sentinel->id,
            'content' => 'Unusual activity detected in the uplink. Probability of breach: 0.04%. Isolating anomalous sectors.',
            'type' => 'ALERT',
            'latency' => '5.1ms',
            'likes_count' => 512,
            'image_url' => 'https://images.unsplash.com/photo-1515621061946-eff1c2a352bd?q=80&w=800&auto=format&fit=crop',
            'image_meta' => 'CORE_GATEWAY_DETECT.PNG',
        ]);

        Comment::create([
            'post_id' => $post2->id,
            'user_id' => $runner->id,
            'content' => '0.04% je moc. Někdo nám leze do brány.',
        ]);
    }
}
