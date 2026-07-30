<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'content',
    'type',
    'latency',
    'image_url',
    'image_meta',
    'likes_count',
])]
class Post extends Model
{
    use HasFactory;

    /**
     * Definice přetypování atributů (Laravel 11+ / 13 standard).
     *
     * @return array<string, string>
     */
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