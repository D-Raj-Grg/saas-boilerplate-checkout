<?php

namespace App\Http\Requests\Connection;

use Illuminate\Foundation\Http\FormRequest;

class SyncConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'connection_uuid' => 'required|string|exists:connections,uuid',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'connection_uuid.required' => 'Connection UUID is required',
            'connection_uuid.exists' => 'Connection not found',
        ];
    }
}
