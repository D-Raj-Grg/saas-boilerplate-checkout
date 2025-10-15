<?php

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'settings' => 'required|array',
            'settings.cookie_lifetime' => 'nullable|integer|min:1|max:365',
            'settings.data_retention_days' => 'nullable|integer|min:1',
            'settings.gdpr_optin' => 'nullable|boolean',
            'settings.gdpr_text' => 'nullable|string|max:1000',
            'settings.gdpr_privacy_link' => 'nullable|url|max:500',
            'settings.enable_subdomain_cookies' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settings.required' => 'Settings are required.',
            'settings.array' => 'Settings must be provided as an array.',
        ];
    }
}
