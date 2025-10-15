<?php

namespace App\Http\Requests\Connection;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConnectionRequest extends FormRequest
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
            'workspace_uuid' => 'required|string|exists:workspaces,uuid',
            'plugin_version' => 'required|string|max:50',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'workspace_uuid.required' => 'Workspace UUID is required',
            'workspace_uuid.exists' => 'Invalid workspace UUID',
            'plugin_version.required' => 'Plugin version is required',
            'plugin_version.string' => 'Plugin version must be a string',
            'plugin_version.max' => 'Plugin version must not exceed 50 characters',
        ];
    }
}
