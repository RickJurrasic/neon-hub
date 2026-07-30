<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFriendshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_id' => [
                'required', 
                'exists:users,id', 
                'different:' . ($this->user()?->id ?? 0),
            ],
        ];
    }
}