<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'name',
    'email',
    'password',
    'handle',
    'role',          // Opraveno z faction na role podle migrace
    'bio',           // Přidáno
    'trust_level',   // Přidáno
    'latency',       // Přidáno
    'avatar_url',
    'is_ai',
    'system_prompt',
    'model',
])]
#[\Illuminate\Database\Eloquent\Attributes\Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public function receivedRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'recipient_id')->where('status', 'pending');
    }

    public function sentRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'sender_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
