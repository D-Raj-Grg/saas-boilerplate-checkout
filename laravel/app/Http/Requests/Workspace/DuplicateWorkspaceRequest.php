<?php

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;

class DuplicateWorkspaceRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'copy_members' => 'boolean',
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
            'name.required' => 'Workspace name is required.',
            'name.string' => 'Workspace name must be a string.',
            'name.max' => 'Workspace name cannot exceed 255 characters.',
            'copy_members.boolean' => 'Copy members must be true or false.',
        ];
    }
}
