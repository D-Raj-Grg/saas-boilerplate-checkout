<?php

namespace App\Traits;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Models\Connection;
use App\Models\Organization;
use App\Models\Workspace;

/**
 * Trait for handling all user permissions (workspace, connection, organization)
 * Following Laravel industry standards with clean separation
 */
trait HasPermissions
{
    /**
     * Check if the user belongs to a specific workspace.
     */
    public function belongsToWorkspace(Workspace $workspace): bool
    {
        // Direct workspace membership
        if ($this->workspaces()->where('workspace_id', $workspace->id)->exists()) {
            return true;
        }

        // Organization admin/owner has implicit access
        /** @var Organization|null $organization */
        $organization = $workspace->organization;

        return $organization && $this->isOrganizationAdmin($organization);
    }

    /**
     * Get the user's role in a specific workspace.
     */
    public function getRoleInWorkspace(Workspace $workspace): ?WorkspaceRole
    {
        // Check if user is direct member first (takes precedence)
        $pivot = $this->workspaces()->where('workspace_id', $workspace->id)->first()?->pivot;
        if ($pivot) {
            return WorkspaceRole::from($pivot->role);
        }

        // If user is org admin/owner, they have manager access
        /** @var Organization|null $organization */
        $organization = $workspace->organization;
        if ($organization && $this->isOrganizationAdmin($organization)) {
            return WorkspaceRole::MANAGER;
        }

        return null;
    }

    /**
     * Check if the user has a specific role in a workspace.
     */
    public function hasRoleInWorkspace(Workspace $workspace, WorkspaceRole $role): bool
    {
        $userRole = $this->getRoleInWorkspace($workspace);

        return $userRole && $userRole === $role;
    }

    /**
     * Check if the user owns a specific organization.
     */
    public function ownsOrganization(Organization $organization): bool
    {
        return $this->isOrganizationOwner($organization);
    }

    /**
     * Check if the user is an admin of a specific organization.
     */
    public function isOrganizationAdminInPermissions(Organization $organization): bool
    {
        return $this->isOrganizationAdmin($organization);
    }

    /**
     * Check if the user can perform an action in a workspace.
     */
    public function canPerformInWorkspace(Workspace $workspace, string $permission): bool
    {
        // Organization admin/owner can do everything
        /** @var \App\Models\Organization $organization */
        $organization = $workspace->organization;
        if ($this->isOrganizationAdmin($organization)) {
            return true;
        }

        $role = $this->getRoleInWorkspace($workspace);
        if (! $role) {
            return false;
        }

        $permissions = config("workspace-permissions.{$role->value}", []);

        return in_array($permission, $permissions);
    }

    /**
     * Check if the user can invite users to a workspace.
     */
    public function canInviteToWorkspace(Workspace $workspace): bool
    {
        return $this->canPerformInWorkspace($workspace, 'workspace.invite_users');
    }

    /**
     * Check if the user can manage a workspace.
     */
    public function canManageWorkspace(Workspace $workspace): bool
    {
        return $this->canPerformInWorkspace($workspace, 'workspace.manage_settings');
    }

    /**
     * Check if the user has access to a workspace.
     */
    public function hasAccessToWorkspace(Workspace $workspace): bool
    {
        return $this->belongsToWorkspace($workspace);
    }

    /**
     * Check if the user has one of the specified roles in a workspace.
     *
     * @param  array<string>  $roles
     */
    public function hasWorkspaceRole(Workspace $workspace, array $roles): bool
    {
        $userRole = $this->getRoleInWorkspace($workspace);
        if (! $userRole) {
            return false;
        }

        return in_array($userRole->value, $roles);
    }

    // === CONNECTION PERMISSIONS ===

    /**
     * Check if the user can view a specific connection.
     */
    public function canViewConnection(Connection $connection): bool
    {
        /** @var \App\Models\Workspace $workspace */
        $workspace = $connection->workspace;

        // Organization admin/owner can view any connection
        /** @var \App\Models\Organization $organization */
        $organization = $workspace->organization;
        if ($this->isOrganizationAdmin($organization)) {
            return true;
        }

        // Admin/Owner can view any connection in workspace
        if ($this->canManageWorkspace($workspace)) {
            return true;
        }

        // Member can only view their own connections
        return $connection->user_id === $this->id;
    }

    /**
     * Check if the user can update a specific connection.
     */
    public function canUpdateConnection(Connection $connection): bool
    {
        /** @var \App\Models\Workspace $workspace */
        $workspace = $connection->workspace;

        // Organization admin/owner can update any connection
        /** @var \App\Models\Organization $organization */
        $organization = $workspace->organization;
        if ($this->isOrganizationAdmin($organization)) {
            return true;
        }

        // Admin/Owner can update any connection in workspace
        if ($this->canManageWorkspace($workspace)) {
            return true;
        }

        // Member can only update their own connections
        return $connection->user_id === $this->id;
    }

    /**
     * Check if the user can delete a specific connection.
     */
    public function canDeleteConnection(Connection $connection): bool
    {
        /** @var \App\Models\Workspace $workspace */
        $workspace = $connection->workspace;

        // Organization admin/owner can delete any connection
        /** @var \App\Models\Organization $organization */
        $organization = $workspace->organization;
        if ($this->isOrganizationAdmin($organization)) {
            return true;
        }

        // Admin/Owner can delete any connection in workspace
        if ($this->canManageWorkspace($workspace)) {
            return true;
        }

        // Member can only delete their own connections
        return $connection->user_id === $this->id;
    }

    // === ORGANIZATION PERMISSIONS ===

    /**
     * Check if the user can manage any workspace in an organization.
     */
    public function canManageAnyWorkspaceInOrganization(Organization $organization): bool
    {
        return $this->isOrganizationAdmin($organization);
    }

    /**
     * Check if the user can create workspaces in an organization.
     */
    public function canCreateWorkspacesInOrganization(Organization $organization): bool
    {
        return $this->isOrganizationAdmin($organization);
    }

    // === PERMISSION COLLECTIONS ===

    /**
     * Get all workspace permissions for the user in a specific workspace.
     *
     * @return array<string, bool>
     */
    public function getWorkspacePermissions(Workspace $workspace): array
    {
        // Organization admin/owner has all permissions
        /** @var \App\Models\Organization $organization */
        $organization = $workspace->organization;
        if ($this->isOrganizationAdmin($organization)) {
            return [
                'can_invite_users' => true,
                'can_remove_users' => true,
                'can_change_user_roles' => true,
                'can_manage_settings' => true,
                'can_delete_workspace' => true,
                'can_update_workspace' => true,
                'can_view_audit_logs' => true,
            ];
        }

        // Check user's role in workspace
        $role = $this->getRoleInWorkspace($workspace);

        if (! $role) {
            return [
                'can_invite_users' => false,
                'can_remove_users' => false,
                'can_change_user_roles' => false,
                'can_manage_settings' => false,
                'can_delete_workspace' => false,
                'can_update_workspace' => false,
                'can_view_audit_logs' => false,
            ];
        }

        // Get permissions from config based on role
        $permissions = config("workspace-permissions.{$role->value}", []);

        return [
            'can_invite_users' => in_array('workspace.invite_users', $permissions),
            'can_remove_users' => in_array('workspace.remove_users', $permissions),
            'can_change_user_roles' => in_array('workspace.change_user_roles', $permissions),
            'can_manage_settings' => in_array('workspace.manage_settings', $permissions),
            'can_delete_workspace' => in_array('workspace.delete', $permissions),
            'can_update_workspace' => in_array('workspace.update', $permissions),
            'can_view_audit_logs' => in_array('workspace.view_audit_logs', $permissions),
        ];
    }

    /**
     * Get all organization permissions for the user in a specific organization.
     * Also includes global-level permissions like can_create_organization.
     *
     * @return array<string, bool>
     */
    public function getOrganizationPermissions(Organization $organization): array
    {
        // Check if user can create new organization (max 1 free org restriction)
        $canCreateOrganization = $this->canCreateNewOrganization();

        // Check organization role for permissions
        $orgRole = $this->getOrganizationRole($organization);
        if ($orgRole && in_array($orgRole, [OrganizationRole::OWNER, OrganizationRole::ADMIN])) {
            return [
                'can_create_workspaces' => true,
                'can_edit_any_workspace' => true,
                'can_delete_any_workspace' => true,
                'can_manage_organization' => true,
                'can_manage_billing' => true,
                'can_transfer_ownership' => true,
                'can_view_all_workspaces' => true,
                'can_create_organization' => $canCreateOrganization,
            ];
        }

        return [
            'can_create_workspaces' => false,
            'can_edit_any_workspace' => false,
            'can_delete_any_workspace' => false,
            'can_manage_organization' => false,
            'can_manage_billing' => false,
            'can_transfer_ownership' => false,
            'can_view_all_workspaces' => false,
            'can_create_organization' => $canCreateOrganization,
        ];
    }

    /**
     * Check if the user can create a new organization.
     * Users can only have 1 free organization.
     */
    public function canCreateNewOrganization(): bool
    {
        // Count how many free plan organizations the user owns
        $freeOrgCount = $this->ownedOrganizations()
            ->whereHas('plans', function ($query) {
                $query->where('slug', 'free')
                    ->where('status', 'active')
                    ->where('is_revoked', false);
            })
            ->count();

        // Users can create org if they don't have a free org (they can upgrade it or buy paid)
        return $freeOrgCount < 1;
    }
}
