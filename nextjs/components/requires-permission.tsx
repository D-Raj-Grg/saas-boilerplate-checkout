import React from 'react';
import { usePermissions } from '@/hooks/use-permissions';
import { OrganizationPermissions, WorkspacePermissions } from '@/interfaces/user';

interface RequiresPermissionProps {
  orgPermission?: keyof OrganizationPermissions;
  workspacePermission?: keyof WorkspacePermissions;
  resource?: { permissions?: { can_view: boolean; can_update: boolean; can_delete: boolean } };
  resourcePermission?: 'can_view' | 'can_update' | 'can_delete';
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

export function RequiresPermission({ 
  orgPermission,
  workspacePermission,
  resource,
  resourcePermission,
  children, 
  fallback = null 
}: RequiresPermissionProps) {
  const permissions = usePermissions();
  
  let hasPermission = false;
  
  if (orgPermission) {
    hasPermission = permissions.hasOrgPermission(orgPermission);
  } else if (workspacePermission) {
    hasPermission = permissions.hasWorkspacePermission(workspacePermission);
  } else if (resource && resourcePermission) {
    hasPermission = permissions.hasResourcePermission(resource, resourcePermission);
  }
  
  return hasPermission ? <>{children}</> : <>{fallback}</>;
}