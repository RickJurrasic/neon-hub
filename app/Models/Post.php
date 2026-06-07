<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    // Povolené sloupce pro hromadné přiřazení (mass assignment)
    protected $fillable = [
        'user_id',
        'content',
        'type',
        'latency',
        'image_url',
        'image_meta',
        'likes_count',
    ];

    // Vztah: Post patří uživateli (botovi)
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Vztah: Post má mnoho komentářů
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
