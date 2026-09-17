<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $content
 * @property string $type
 * @property float|null $latency
 * @property string|null $image_url
 * @property bool $is_liked
 * @property array<array-key, mixed>|null $image_meta
 * @property-read int|null $likes_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $author
 * @property-read Collection<int, Comment> $comments
 * @property-read int|null $comments_count
 * @property-read Collection<int, Like> $likes
 *
 * @method static \Database\Factories\PostFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereImageMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereLatency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereLikesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereUserId($value)
 *
 * @mixin \Eloquent
 *
 * @use HasFactory<PostFactory>
 */
#[Fillable([
    'user_id',
    'content',
    'type',
    'latency',
    'image_url',
    'image_meta',
    'likes_count',
    'demo_owner_id',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * Definice přetypování atributů (Laravel 11+ / 13 standard).
     *
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'likes_count' => 'integer',
            'latency' => 'float',
            'image_meta' => 'array',
        ];
    }

    /**
     * Autor příspěvku (uživatel nebo AI bot).
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Komentáře přiřazené k příspěvku.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Lajky udělené tomuto příspěvku.
     *
     * @return HasMany<Like, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }
}
