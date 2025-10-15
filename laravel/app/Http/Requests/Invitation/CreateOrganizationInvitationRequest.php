<?php

namespace App\Http\Requests\Invitation;

use App\Enums\WorkspaceRole;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrganizationInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $organization = $user?->currentOrganization;

            if (! $user || ! $organization) {
                return;
            }

            // If user is org admin, no additional restrictions
            if ($user->isOrganizationAdmin($organization)) {
                return;
            }

            // For workspace editors (members with edit permission)
            $currentWorkspace = $user->currentWorkspace;
            if ($currentWorkspace && $currentWorkspace->organization_id === $organization->id) {
                $hasEditorRole = $user->hasWorkspaceRole($currentWorkspace, [
                    WorkspaceRole::EDITOR->value,
                    WorkspaceRole::MANAGER->value,
                ]);

                if ($hasEditorRole) {
                    // Workspace editors can only invite as "member" role
                    if ($this->input('role') !== 'member') {
                        $validator->errors()->add('role', 'You can only invite users with "member" organization role.');
                    }

                    // Workspace editors must assign to their current workspace only
                    $workspaceAssignments = $this->input('workspace_assignments', []);
                    if (empty($workspaceAssignments)) {
                        $validator->errors()->add('workspace_assignments', 'You must assign the user to your current workspace.');
                    } else {
                        foreach ($workspaceAssignments as $index => $assignment) {
                            // Check if it's the current workspace
                            if (($assignment['workspace_id'] ?? null) !== $currentWorkspace->uuid) {
                                $validator->errors()->add(
                                    "workspace_assignments.{$index}.workspace_id",
                                    'You can only invite users to your current workspace.'
                                );
                            }

                            // Check role - only viewer or editor allowed
                            $assignedRole = $assignment['role'] ?? null;
                            if (! in_array($assignedRole, [WorkspaceRole::VIEWER->value, WorkspaceRole::EDITOR->value])) {
                                $validator->errors()->add(
                                    "workspace_assignments.{$index}.role",
                                    'You can only assign "viewer" or "editor" workspace roles.'
                                );
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,member',
            'message' => 'nullable|string|max:500',
        ];

        // Workspace assignments are required only for members
        if ($this->input('role') === 'member') {
            $rules['workspace_assignments'] = 'required|array|min:1';
            $rules['workspace_assignments.*.workspace_id'] = 'required|uuid';
            $rules['workspace_assignments.*.role'] = 'required|in:'.implode(',', WorkspaceRole::values());
        } else {
            // Admins don't need workspace assignments
            $rules['workspace_assignments'] = 'nullable|array';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'Email address cannot exceed 255 characters.',
            'role.required' => 'Organization role is required.',
            'role.in' => 'Organization role must be either admin or member.',
            'workspace_assignments.required' => 'Members must be assigned to at least one workspace.',
            'workspace_assignments.min' => 'Members must be assigned to at least one workspace.',
            'workspace_assignments.*.workspace_id.required' => 'Workspace ID is required for each assignment.',
            'workspace_assignments.*.workspace_id.uuid' => 'Workspace ID must be a valid UUID.',
            'workspace_assignments.*.role.required' => 'Workspace role is required for each assignment.',
            'workspace_assignments.*.role.in' => 'Workspace role must be one of: '.implode(', ', WorkspaceRole::values()).'.',
            'message.max' => 'Message cannot exceed 500 characters.',
        ];
    }
}
