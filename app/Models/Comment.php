<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[\Illuminate\Database\Eloquent\Attributes\Fillable([
    'post_id',
    'user_id',
    'content',
])]
class Comment extends Model
{
    // Komentář patří nějakému postu
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // Komentář má svého autora
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
