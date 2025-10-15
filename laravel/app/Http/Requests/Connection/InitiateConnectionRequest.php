<?php

namespace App\Http\Requests\Connection;

use Illuminate\Foundation\Http\FormRequest;

class InitiateConnectionRequest extends FormRequest
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
            'redirect_url' => 'required|url|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'redirect_url.required' => 'Redirect URL is required',
            'redirect_url.url' => 'Redirect URL must be a valid URL',
        ];
    }
}
