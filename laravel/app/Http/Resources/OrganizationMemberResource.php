<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array $memberData */
        $memberData = $this->resource;
        $user = $memberData['user'];

        return [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),

            // Organization membership data
            'organization_role' => $memberData['organization_role'],
            'joined_at' => $memberData['joined_at'],
            'is_owner' => $memberData['is_owner'],

            // Workspace access info
            'workspace_access' => $memberData['workspace_access'],
            'accessible_workspaces_count' => count($memberData['workspace_access']),
        ];
    }
}
