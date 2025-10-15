import { useCallback } from 'react';
import { useUserStore } from '@/stores/user-store';
import { OrganizationPermissions, WorkspacePermissions } from '@/interfaces/user';

export function usePermissions() {
  const { userData } = useUserStore();

  const hasOrgPermission = useCallback((permission: keyof OrganizationPermissions) => {
    return userData?.current_organization_permissions?.[permission] ?? false;
  }, [userData?.current_organization_permissions]);

  const hasWorkspacePermission = useCallback((permission: keyof WorkspacePermissions) => {
    return userData?.current_workspace_permissions?.[permission] ?? false;
  }, [userData?.current_workspace_permissions]);

  const hasResourcePermission = useCallback((resource: { permissions?: { can_view: boolean; can_update: boolean; can_delete: boolean } } | undefined, permission: 'can_view' | 'can_update' | 'can_delete') => {
    return resource?.permissions?.[permission] ?? false;
  }, []);

  return {
    // Organization permissions
    canManageOrganization: hasOrgPermission('can_manage_organization'),
    canCreateWorkspaces: hasOrgPermission('can_create_workspaces'),
    canEditAnyWorkspace: hasOrgPermission('can_edit_any_workspace'),
    canDeleteAnyWorkspace: hasOrgPermission('can_delete_any_workspace'),
    canManageBilling: hasOrgPermission('can_manage_billing'),
    canTransferOwnership: hasOrgPermission('can_transfer_ownership'),
    canViewAllWorkspaces: hasOrgPermission('can_view_all_workspaces'),
    canCreateOrganization: hasOrgPermission('can_create_organization'),

    // Workspace permissions
    canInviteUsers: hasWorkspacePermission('can_invite_users'),
    canRemoveUsers: hasWorkspacePermission('can_remove_users'),
    canChangeUserRoles: hasWorkspacePermission('can_change_user_roles'),
    canManageSettings: hasWorkspacePermission('can_manage_settings'),
    canDeleteWorkspace: hasWorkspacePermission('can_delete_workspace'),
    canUpdateWorkspace: hasWorkspacePermission('can_update_workspace'),
    canViewAuditLogs: hasWorkspacePermission('can_view_audit_logs'),

    // Helper functions for dynamic checks
    hasOrgPermission,
    hasWorkspacePermission,
    hasResourcePermission,
  };
}