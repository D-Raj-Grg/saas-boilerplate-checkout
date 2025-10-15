<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;

class UserSessionService
{
    /**
     * Set user's current organization and find appropriate workspace.
     *
     * @return array<string, mixed>
     */
    public function setCurrentOrganization(User $user, Organization $organization): array
    {
        // Update current organization
        $user->current_organization_id = $organization->id;

        // Find a workspace in this organization that the user has access to
        $workspace = $this->findUserWorkspaceInOrganization($user, $organization);

        if ($workspace) {
            $user->current_workspace_id = $workspace->id;
        } else {
            // If user is org admin/owner but not in any workspace, find first workspace
            if ($user->isOrganizationAdmin($organization)) {
                $firstWorkspace = $organization->workspaces()->first();
                if ($firstWorkspace instanceof Workspace) {
                    $user->current_workspace_id = $firstWorkspace->id;
                }
            } else {
                $user->current_workspace_id = null;
            }
        }

        $user->save();

        // Load relationships for response
        $user->load(['currentOrganization.activePlans', 'currentWorkspace.organization']);

        return $this->formatOrganizationResponse($user, $organization);
    }

    /**
     * Set user's current workspace and update organization context.
     *
     * @return array<string, mixed>
     */
    public function setCurrentWorkspace(User $user, Workspace $workspace): array
    {
        // Update current workspace and organization
        $user->current_workspace_id = $workspace->id;
        $user->current_organization_id = $workspace->organization_id;
        $user->save();

        // Load relationships for response
        $workspace->load('organization.activePlans');

        return $this->formatWorkspaceResponse($user, $workspace);
    }

    /**
     * Clear user's current session.
     */
    public function clearSession(User $user): void
    {
        $user->current_organization_id = null;
        $user->current_workspace_id = null;
        $user->save();
    }

    /**
     * Find a workspace in the organization that the user has access to.
     */
    private function findUserWorkspaceInOrganization(User $user, Organization $organization): ?Workspace
    {
        $workspace = $organization->workspaces()
            ->whereHas('users', fn ($query) => $query->where('user_id', $user->id))
            ->first();

        return $workspace instanceof Workspace ? $workspace : null;
    }

    /**
     * Format organization response data.
     *
     * @return array<string, mixed>
     */
    private function formatOrganizationResponse(User $user, Organization $organization): array
    {
        /** @var \App\Models\Workspace|null $currentWorkspace */
        $currentWorkspace = $user->currentWorkspace;
        /** @var \App\Models\Plan|null $plan */
        $plan = $organization->getCurrentPlan();

        return [
            'organization' => [
                'uuid' => $organization->uuid,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'is_owner' => $user->isOrganizationOwner($organization),
                'plan' => $plan ? [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                ] : null,
            ],
            'workspace' => $currentWorkspace ? [
                'uuid' => $currentWorkspace->uuid,
                'name' => $currentWorkspace->name,
                'slug' => $currentWorkspace->slug,
                'role' => $user->getRoleInWorkspace($currentWorkspace)?->value,
            ] : null,
        ];
    }

    /**
     * Format workspace response data.
     *
     * @return array<string, mixed>
     */
    private function formatWorkspaceResponse(User $user, Workspace $workspace): array
    {
        /** @var \App\Models\Organization $organization */
        $organization = $workspace->organization;
        /** @var \App\Models\Plan|null $plan */
        $plan = $organization->getCurrentPlan();

        return [
            'workspace' => [
                'uuid' => $workspace->uuid,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'role' => $user->getRoleInWorkspace($workspace)?->value,
                'organization' => [
                    'uuid' => $organization->uuid,
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                    'plan' => $plan ? [
                        'name' => $plan->name,
                        'slug' => $plan->slug,
                    ] : null,
                ],
            ],
        ];
    }
}
