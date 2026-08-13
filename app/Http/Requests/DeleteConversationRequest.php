<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class DeleteConversationRequest extends FormRequest
{
    /**
     * Gate: only the conversation owner may delete.
     * Ownership is the single agent_conversations.user_id column (no participants table),
     * matching the listing contract in MessageService::getIndexMessages and the
     * authorize() convention used by UpdateCommentRequest.
     */
    public function authorize(): bool
    {
        $userId = (int) auth()->id();

        if ($userId === 0) {
            return false;
        }

        return DB::table('agent_conversations')
            ->where('id', (string) $this->route('id'))
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
