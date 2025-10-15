<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view any organizations.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view organizations they have access to
    }

    /**
     * Determine whether the user can view the organization.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->ownsOrganization($organization) ||
               $user->accessibleOrganizations()->where('organizations.id', $organization->id)->exists();
    }

    /**
     * Determine whether the user can create organizations.
     */
    public function create(User $user): bool
    {
        // Allow creating organizations - specific plan restrictions are enforced in CreateOrganizationRequest
        return true;
    }

    /**
     * Determine whether the user can update the organization.
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can delete the organization.
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $user->ownsOrganization($organization);
    }

    /**
     * Determine whether the user can restore the organization.
     */
    public function restore(User $user, Organization $organization): bool
    {
        return $user->ownsOrganization($organization);
    }

    /**
     * Determine whether the user can permanently delete the organization.
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        return $user->ownsOrganization($organization);
    }

    /**
     * Determine whether the user can manage organization settings.
     */
    public function manageSettings(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can manage organization billing.
     */
    public function manageBilling(User $user, Organization $organization): bool
    {
        return $user->ownsOrganization($organization);
    }

    /**
     * Determine whether the user can change organization plan.
     */
    public function changePlan(User $user, Organization $organization): bool
    {
        return $user->ownsOrganization($organization);
    }

    /**
     * Determine whether the user can create workspaces in the organization.
     */
    public function createWorkspace(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can view organization audit logs.
     */
    public function viewAuditLogs(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can export organization data.
     */
    public function exportData(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can transfer organization ownership.
     */
    public function transferOwnership(User $user, Organization $organization): bool
    {
        return $user->ownsOrganization($organization);
    }

    /**
     * Determine whether the user can invite users to the organization.
     * Organization admins/owners can always invite.
     * Organization members with EDITOR role in their current workspace can also invite
     * (but with restrictions - only to their workspace as members).
     */
    public function inviteUsers(User $user, Organization $organization): bool
    {
        // Admins and owners can always invite
        if ($user->isOrganizationAdmin($organization)) {
            return true;
        }

        // Members with EDITOR role in current workspace can invite to that workspace only
        if ($user->belongsToOrganization($organization)) {
            $currentWorkspace = $user->currentWorkspace;
            if ($currentWorkspace && $currentWorkspace->organization_id === $organization->id) {
                return $user->hasWorkspaceRole($currentWorkspace, [
                    \App\Enums\WorkspaceRole::EDITOR->value,
                    \App\Enums\WorkspaceRole::MANAGER->value,
                ]);
            }
        }

        return false;
    }

    /**
     * Determine whether the user can remove users from the organization.
     */
    public function removeUsers(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can manage organization integrations.
     */
    public function manageIntegrations(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can view organization analytics.
     */
    public function viewAnalytics(User $user, Organization $organization): bool
    {
        return $user->belongsToOrganization($organization);
    }

    /**
     * Determine whether the user can switch to this organization.
     */
    public function switchTo(User $user, Organization $organization): bool
    {
        return $this->view($user, $organization);
    }

    /**
     * Determine whether the user can manage any workspace in the organization.
     */
    public function manageAnyWorkspace(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can edit any workspace in the organization.
     */
    public function editAnyWorkspace(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can delete any workspace in the organization.
     */
    public function deleteAnyWorkspace(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    /**
     * Determine whether the user can view all workspaces in the organization.
     */
    public function viewAllWorkspaces(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }
}
