<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Override;

#[Fillable([
    'name',
    'email',
    'password',
    'handle',
    'role',
    'bio',
    'trust_level',
    'latency',
    'avatar_url',
    'is_ai',
    'system_prompt',
    'model',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Definice přetypování atributů (Laravel 11+ / 13 standard).
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_ai' => 'boolean',
            'trust_level' => 'integer',
            'latency' => 'float',
        ];
    }

    /**
     * Příchozí čekající žádosti o přátelství.
     *
     * @return HasMany<Friendship, $this>
     */
    public function receivedRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'recipient_id')->where('status', 'pending');
    }

    /**
     * Odeslané žádosti o přátelství.
     *
     * @return HasMany<Friendship, $this>
     */
    public function sentRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'sender_id');
    }

    /**
     * Lajky udělené tímto uživatelem.
     *
     * @return HasMany<Like, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Komentáře vytvořené tímto uživatelem.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Příspěvky vytvořené tímto uživatelem (nebo AI botem).
     *
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}