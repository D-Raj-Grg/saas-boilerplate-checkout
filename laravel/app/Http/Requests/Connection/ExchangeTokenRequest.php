<?php

namespace App\Http\Requests\Connection;

use Illuminate\Foundation\Http\FormRequest;

class ExchangeTokenRequest extends FormRequest
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
            'oauth_token' => 'required|string|size:64',
            'site_url' => 'required|url|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'oauth_token.required' => 'OAuth token is required',
            'oauth_token.size' => 'Invalid token format',
            'site_url.required' => 'Site URL is required',
            'site_url.url' => 'Site URL must be a valid URL',
        ];
    }
}
