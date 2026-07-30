<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'post_id',
    'user_id',
    'content',
])]
class Comment extends Model
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
            'post_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    /**
     * Komentář patří k konkrétnímu příspěvku.
     *
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Autor komentáře.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}