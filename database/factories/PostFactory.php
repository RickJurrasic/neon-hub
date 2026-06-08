<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Definice výchozího stavu pro testovací post.
     */
    public function definition(): array
    {
        return [
            // Pokud v testu nepředáš konkrétního uživatele, factory automaticky vyrobí nového
            'user_id' => User::factory(),
            'content' => $this->faker->realText(100),
            'type' => $this->faker->randomElement(['status', 'log', 'alert']),
            'latency' => $this->faker->randomFloat(1, 0.5, 45).'ms_STABLE',
            'image_url' => null, // Výchozí bez obrázku
            'image_meta' => null,
        ];
    }
}
