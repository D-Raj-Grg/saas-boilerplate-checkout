export interface UserPlan {
  name: string;
  slug: string;
  features?: string[];
}

export interface UserOrganization {
  uuid: string;
  name: string;
  slug: string;
  is_owner: boolean;
  current_plan?: {
    name: string;
    slug: string;
  };
}

export interface OrganizationPermissions {
  can_create_workspaces: boolean;
  can_edit_any_workspace: boolean;
  can_delete_any_workspace: boolean;
  can_manage_organization: boolean;
  can_manage_billing: boolean;
  can_transfer_ownership: boolean;
  can_view_all_workspaces: boolean;
  can_create_organization: boolean;
}


export interface WorkspacePermissions {
  can_invite_users: boolean;
  can_remove_users: boolean;
  can_change_user_roles: boolean;
  can_manage_settings: boolean;
  can_delete_workspace: boolean;
  can_update_workspace: boolean;
  can_view_audit_logs: boolean;
}

export interface UserWorkspace {
  uuid: string;
  name: string;
  slug: string;
  organization_uuid: string;
  role: string;
  description?: string;
}

export interface PendingInvitation {
  token: string;
  role: string;
  message?: string;
  expires_at: string;
  workspace: {
    uuid: string;
    name: string;
    organization: {
      uuid: string;
      name: string;
    };
  };
  inviter: {
    name: string;
    email: string;
  };
  created_at: string;
}


export interface User {
  name: string;
  first_name: string;
  last_name: string;
  email: string;
  email_verified_at?: string;
  current_organization_uuid: string;
  current_workspace_uuid: string;
}


export interface UserData {
  user: User;
  organizations: UserOrganization[];
  workspaces: UserWorkspace[];
  current_workspace_permissions: WorkspacePermissions;
  current_workspace_pending_invitations: PendingInvitation[];
  current_organization_permissions: OrganizationPermissions;
  current_organization_plan_limits?: import("@/types/plan").PlanLimits;
}

export interface UserStore {
  userData: UserData | null;
  selectedOrganization: UserOrganization | null;
  selectedWorkspace: UserWorkspace | null;
  isInitialized: boolean;
  setUserData: (userData: UserData | null) => void;
  clearUser: () => void;
  getInitials: () => string;
  setSelectedOrganization: (organization: UserOrganization | null) => void;
  setSelectedWorkspace: (workspace: UserWorkspace | null) => void;
  setIsInitialized: (initialized: boolean) => void;

  // Legacy methods for backward compatibility
  get user(): User | null;
  setUser: (user: User | null) => void;
  updateUserOrganizations: (organizations: UserOrganization[]) => void;
  updateUserWorkspaces: (workspaces: UserWorkspace[]) => void;
}