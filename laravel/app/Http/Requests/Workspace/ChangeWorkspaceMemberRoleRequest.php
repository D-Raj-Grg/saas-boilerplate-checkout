<?php

namespace App\Http\Requests\Workspace;

use App\Enums\WorkspaceRole;
use Illuminate\Foundation\Http\FormRequest;

class ChangeWorkspaceMemberRoleRequest extends FormRequest
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
            'role' => 'required|in:'.implode(',', WorkspaceRole::values()),
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
            'role.required' => 'Role is required.',
            'role.in' => 'Role must be one of: '.implode(', ', WorkspaceRole::values()).'.',
        ];
    }
}
