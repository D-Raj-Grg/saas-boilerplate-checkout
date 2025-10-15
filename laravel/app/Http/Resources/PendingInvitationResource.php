<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Invitation
 */
class PendingInvitationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => null, // Invitations don't have user UUID yet
            'name' => null, // Will be filled when user accepts
            'first_name' => null,
            'last_name' => null,
            'email' => $this->email,
            'email_verified_at' => null, // Not applicable for pending invitations
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Workspace membership data (from invitation)
            'role' => $this->role,
            'joined_at' => null, // Not joined yet
            'is_org_admin_access' => in_array($this->role, ['admin', 'owner']),

            // Organization membership data
            'organization_role' => $this->role,

            // Invitation-specific data
            'invitation_status' => $this->status,
            'invitation_uuid' => $this->uuid,
            'expires_at' => $this->expires_at->toIso8601String(),
            'message' => $this->message,
            'invited_by' => $this->whenLoaded('inviter', function () {
                return $this->inviter ? [
                    'name' => $this->inviter->name,
                    'email' => $this->inviter->email,
                ] : null;
            }),

            // Workspace assignments (only for member role invitations)
            'workspace_assignments' => $this->when(
                $this->role === 'member' && ! empty($this->workspace_assignments),
                function () {
                    if (! is_array($this->workspace_assignments)) {
                        return [];
                    }

                    $workspaces = collect($this->workspace_assignments)->map(function ($assignment) {
                        // Find the workspace by UUID to get the name
                        $workspace = \App\Models\Workspace::where('uuid', $assignment['workspace_id'])
                            ->where('organization_id', $this->organization_id)
                            ->first();

                        return [
                            'workspace_uuid' => $assignment['workspace_id'],
                            'workspace_name' => $workspace->name ?? 'Unknown Workspace',
                            'role' => $assignment['role'] ?? 'viewer',
                        ];
                    });

                    return $workspaces->toArray();
                },
                []
            ),

            // For admin/owner invitations, they get access to all workspaces
            'will_have_access_to_all_workspaces' => in_array($this->role, ['admin', 'owner']),
        ];
    }
}
