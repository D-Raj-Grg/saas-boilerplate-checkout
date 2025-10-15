<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    /**
     * Get the hierarchy level of a role.
     */
    protected function getRoleHierarchyLevel(WorkspaceRole $role): int
    {
        return config("workspace-permissions.hierarchy.{$role->value}", 0);
    }

    /**
     * Check if one role is higher than another.
     */
    protected function isRoleHigherThan(WorkspaceRole $role1, WorkspaceRole $role2): bool
    {
        return $this->getRoleHierarchyLevel($role1) > $this->getRoleHierarchyLevel($role2);
    }

    /**
     * Determine whether the user can view any workspaces.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view workspaces they have access to
    }

    /**
     * Determine whether the user can view the workspace.
     */
    public function view(User $user, Workspace $workspace): bool
    {
        return $user->belongsToWorkspace($workspace) || $this->userOwnsWorkspaceOrganization($user, $workspace);
    }

    /**
     * Determine whether the user can create workspaces.
     */
    public function create(User $user): bool
    {
        // Users can create workspaces if they have access to an organization
        return $user->accessibleOrganizations()->exists();
    }

    /**
     * Determine whether the user can update the workspace.
     */
    public function update(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.update') || $this->userOwnsWorkspaceOrganization($user, $workspace);
    }

    /**
     * Determine whether the user can delete the workspace.
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.delete') || $this->userOwnsWorkspaceOrganization($user, $workspace);
    }

    /**
     * Determine whether the user can restore the workspace.
     */
    public function restore(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.delete');
    }

    /**
     * Determine whether the user can permanently delete the workspace.
     */
    public function forceDelete(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.delete');
    }

    /**
     * Determine whether the user can manage workspace settings.
     */
    public function manageSettings(User $user, Workspace $workspace): bool
    {
        return $user->canManageWorkspace($workspace);
    }

    /**
     * Determine whether the user can invite users to the workspace.
     */
    public function inviteUsers(User $user, Workspace $workspace): bool
    {
        return $user->canInviteToWorkspace($workspace);
    }

    /**
     * Determine whether the user can remove users from the workspace.
     */
    public function removeUsers(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.remove_users') || $this->userOwnsWorkspaceOrganization($user, $workspace);
    }

    /**
     * Determine whether the user can change user roles in the workspace.
     */
    public function changeUserRoles(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.change_user_roles') || $this->userOwnsWorkspaceOrganization($user, $workspace);
    }

    /**
     * Determine whether the user can view workspace members.
     */
    public function viewMembers(User $user, Workspace $workspace): bool
    {
        return $user->belongsToWorkspace($workspace);
    }

    /**
     * Determine whether the user can view workspace analytics.
     */
    public function viewAnalytics(User $user, Workspace $workspace): bool
    {
        return $user->belongsToWorkspace($workspace);
    }

    /**
     * Determine whether the user can view workspace audit logs.
     */
    public function viewAuditLogs(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.view_audit_logs');
    }

    /**
     * Determine whether the user can export workspace data.
     */
    public function exportData(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.manage_settings');
    }

    /**
     * Determine whether the user can manage workspace integrations.
     */
    public function manageIntegrations(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.manage_settings');
    }

    /**
     * Determine whether the user can use workspace features.
     */
    public function useFeatures(User $user, Workspace $workspace): bool
    {
        return $user->belongsToWorkspace($workspace);
    }

    /**
     * Determine whether the user can leave the workspace.
     */
    public function leave(User $user, Workspace $workspace): bool
    {
        if (! $user->belongsToWorkspace($workspace)) {
            return false;
        }

        $role = $user->getRoleInWorkspace($workspace);

        // Owner cannot leave if they are the only owner
        if ($role === WorkspaceRole::MANAGER) {
            $ownerCount = $workspace->users()->wherePivot('role', WorkspaceRole::MANAGER->value)->count();

            return $ownerCount > 1;
        }

        return true;
    }

    /**
     * Determine whether the user can transfer workspace ownership.
     */
    public function transferOwnership(User $user, Workspace $workspace): bool
    {
        return $user->hasRoleInWorkspace($workspace, WorkspaceRole::MANAGER);
    }

    /**
     * Determine whether the user can remove a specific user from the workspace.
     */
    public function removeUser(User $user, Workspace $workspace, User $targetUser): bool
    {
        if (! $user->canPerformInWorkspace($workspace, 'workspace.remove_users')) {
            return false;
        }

        $userRole = $user->getRoleInWorkspace($workspace);
        $targetRole = $targetUser->getRoleInWorkspace($workspace);

        if (! $userRole || ! $targetRole) {
            return false;
        }

        // Can't remove users with equal or higher role
        return $this->isRoleHigherThan($userRole, $targetRole);
    }

    /**
     * Determine whether the user can change a specific user's role in the workspace.
     */
    public function changeUserRole(User $user, Workspace $workspace, User $targetUser, WorkspaceRole $newRole): bool
    {
        if (! $user->canPerformInWorkspace($workspace, 'workspace.change_user_roles')) {
            return false;
        }

        $userRole = $user->getRoleInWorkspace($workspace);
        $targetRole = $targetUser->getRoleInWorkspace($workspace);

        if (! $userRole || ! $targetRole) {
            return false;
        }

        // Can't change roles of users with equal or higher role
        if (! $this->isRoleHigherThan($userRole, $targetRole)) {
            return false;
        }

        // Can't assign roles higher than or equal to your own role
        if (! $this->isRoleHigherThan($userRole, $newRole)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can invite a user with a specific role.
     */
    public function inviteUserWithRole(User $user, Workspace $workspace, WorkspaceRole $role): bool
    {
        if (! $user->canPerformInWorkspace($workspace, 'workspace.invite_users')) {
            return false;
        }

        $userRole = $user->getRoleInWorkspace($workspace);

        if (! $userRole) {
            return false;
        }

        // Can't invite users with equal or higher role
        return $this->isRoleHigherThan($userRole, $role);
    }

    /**
     * Determine whether the user can add members to the workspace.
     */
    public function addMembers(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.invite_users');
    }

    /**
     * Determine whether the user can remove members from the workspace.
     */
    public function removeMembers(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.remove_users');
    }

    /**
     * Determine whether the user can change roles in the workspace.
     */
    public function changeRoles(User $user, Workspace $workspace): bool
    {
        return $user->canPerformInWorkspace($workspace, 'workspace.change_user_roles');
    }

    /**
     * Determine whether the user can duplicate the workspace.
     */
    public function duplicate(User $user, Workspace $workspace): bool
    {
        return $user->hasRoleInWorkspace($workspace, WorkspaceRole::MANAGER) ||
               $user->hasRoleInWorkspace($workspace, WorkspaceRole::EDITOR);
    }

    /**
     * Determine whether the user can switch to this workspace.
     */
    public function switchTo(User $user, Workspace $workspace): bool
    {
        return $this->view($user, $workspace);
    }

    /**
     * Helper method to check if user owns the workspace's organization.
     */
    private function userOwnsWorkspaceOrganization(User $user, Workspace $workspace): bool
    {
        /** @var \App\Models\Organization $organization */
        $organization = $workspace->organization;

        // Organization admin/owner has full control over workspaces
        return $user->isOrganizationAdmin($organization);
    }
}
