<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin User
 *
 * @property string|null $avatar
 * @property string|null $avatar_url
 * @property string|null $bio
 * @property string|null $latency
 * @property int|null $trust_level
 */
class UserResource extends JsonResource
{
    /**
     * Transformace dat uživatele pro frontend / Inertia props.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->id,
            'name' => $this->name ?? 'UNKNOWN_ENTITY',
            'email' => $this->email,
            'role' => $this->role ?? 'DEFENSE',
            'bio' => $this->bio ?? '"Šifrované bio prázdné."',
            'trust_level' => $this->trust_level ?? 88,
            'latency' => $this->latency ?? '12ms_STABLE',
            'avatar' => $this->avatar_url ?? $this->avatar,
            'avatar_url' => $this->avatar_url ?? $this->avatar,
        ];
    }
}
