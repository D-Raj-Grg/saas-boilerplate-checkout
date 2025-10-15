<?php

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // SECURITY: Removed organization_id requirement - now using user's current organization context
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|alpha_dash',
            'description' => 'nullable|string|max:1000',
            'settings' => 'nullable|array',
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // SECURITY: Removed organization_id validation messages - now using user's current organization context
            'name.required' => 'Workspace name is required',
            'name.max' => 'Workspace name cannot exceed 255 characters',
            'slug.alpha_dash' => 'Workspace slug can only contain letters, numbers, dashes, and underscores',
        ];
    }
}
