<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_id' => ['required', 'string'], // případně přidat 'uuid' / 'ulid'
            'text' => ['required', 'string', 'max:2000'],
        ];
    }
}