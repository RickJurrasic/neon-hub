<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'handle',
        'faction',
        'avatar_url',
        'is_ai',
        'system_prompt',
        'model',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Vztah: Uživatel přijal žádosti o přátelství
     */
    public function receivedRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'recipient_id')->where('status', 'pending');
    }

    /**
     * Vztah: Uživatel odeslal žádosti o přátelství
     */
    public function sentRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'sender_id');
    }

    /**
     * Vztah: Uživatel vytvořil lajky
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Vztah: Uživatel vytvořil komentáře
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
