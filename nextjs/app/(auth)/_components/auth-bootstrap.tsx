"use client";

import { useEffect } from "react";
import { useUserStore } from "@/stores/user-store";
import { User, UserOrganization, UserWorkspace, PendingInvitation, UserData, WorkspacePermissions, OrganizationPermissions } from "@/interfaces/user";
import { PlanLimits } from "@/types/plan";

interface AuthBootstrapProps {
  children: React.ReactNode;
  user: User;
  organizations: UserOrganization[];
  workspaces: UserWorkspace[];
  current_workspace_permissions: WorkspacePermissions;
  current_workspace_pending_invitations: PendingInvitation[];
  current_organization_permissions: OrganizationPermissions;
  current_organization_plan_limits?: PlanLimits;
}

export function AuthBootstrap({
  children,
  user,
  organizations,
  workspaces,
  current_workspace_permissions,
  current_workspace_pending_invitations,
  current_organization_permissions,
  current_organization_plan_limits
}: AuthBootstrapProps) {
  // Hydrate the store with server data
  useEffect(() => {
    const userData: UserData = {
      user,
      organizations,
      workspaces,
      current_workspace_permissions,
      current_workspace_pending_invitations,
      current_organization_permissions,
      current_organization_plan_limits
    };

    // Use setUserData to handle all the logic including auto-selection
    useUserStore.getState().setUserData(userData);
    useUserStore.getState().setIsInitialized(true);
  }, [user, organizations, workspaces, current_workspace_permissions, current_workspace_pending_invitations, current_organization_permissions, current_organization_plan_limits]);

  return <>{children}</>;
}