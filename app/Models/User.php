<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $handle
 * @property string $role
 * @property string|null $bio
 * @property int $trust_level
 * @property float $latency
 * @property string|null $avatar_url
 * @property bool $is_ai
 * @property string|null $faction
 * @property string|null $status_text
 * @property string|null $system_prompt
 * @property string|null $model
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Comment> $comments
 * @property-read int|null $comments_count
 * @property-read Collection<int, Like> $likes
 * @property-read int|null $likes_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Post> $posts
 * @property-read int|null $posts_count
 * @property-read Collection<int, Friendship> $receivedRequests
 * @property-read int|null $received_requests_count
 * @property-read Collection<int, Friendship> $sentRequests
 * @property-read int|null $sent_requests_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFaction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHandle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLatency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatusText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSystemPrompt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTrustLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 *
 * @use HasFactory<UserFactory>
 */
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
    'faction',
    'status_text',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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
