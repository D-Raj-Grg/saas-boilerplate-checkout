<?php

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ChangeMemberRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Will use policy in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var \App\Models\User $user */
        $user = $this->user();
        $organizationId = $user->current_organization_id;

        return [
            'role' => ['required', new Enum(OrganizationRole::class)],
            'workspace_assignments' => ['sometimes', 'array'],
            'workspace_assignments.*.workspace_id' => [
                'required_with:workspace_assignments',
                'string',
                'exists:workspaces,uuid,organization_id,'.$organizationId,
            ],
            'workspace_assignments.*.role' => ['required_with:workspace_assignments', new Enum(WorkspaceRole::class)],
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
            'role.required' => 'Organization role is required',
            'role.enum' => 'Invalid organization role',
            'workspace_assignments.*.workspace_id.required_with' => 'Workspace ID is required for workspace assignments',
            'workspace_assignments.*.workspace_id.exists' => 'Workspace not found',
            'workspace_assignments.*.role.required_with' => 'Workspace role is required for workspace assignments',
            'workspace_assignments.*.role.enum' => 'Invalid workspace role',
        ];
    }
}
