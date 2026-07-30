<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sender_id',
    'recipient_id',
    'status',
    'message',
])]
class Friendship extends Model
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
            'sender_id' => 'integer',
            'recipient_id' => 'integer',
        ];
    }

    // --- RELACE ---

    /**
     * Kdo poslal žádost o přátelství.
     *
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Komu byla žádost určena.
     *
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    // --- SCOPES ---

    /**
     * Vyhledá vazbu mezi dvěma uživateli bez ohledu na směr žádosti.
     *
     * @param  Builder<Friendship>  $query
     * @return Builder<Friendship>
     */
    public function scopeBetween(Builder $query, int $userA, int $userB): Builder
    {
        return $query->where(function (Builder $q) use ($userA, $userB) {
            $q->where('sender_id', $userA)->where('recipient_id', $userB);
        })->orWhere(function (Builder $q) use ($userA, $userB) {
            $q->where('sender_id', $userB)->where('recipient_id', $userA);
        });
    }

    /**
     * Filtruje pouze schválená přátelství.
     *
     * @param  Builder<Friendship>  $query
     * @return Builder<Friendship>
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Filtruje pouze čekající žádosti.
     *
     * @param  Builder<Friendship>  $query
     * @return Builder<Friendship>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}