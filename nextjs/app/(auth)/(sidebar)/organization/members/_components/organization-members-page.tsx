"use client";

import { useUserStore } from "@/stores/user-store";
import { OrganizationMembersManagement } from "../../_components/organization-members-management";

interface OrganizationMember {
  uuid: string;
  name: string;
  email: string;
  organization_role: "owner" | "admin" | "member";
  is_owner: boolean;
  joined_at: string;
  workspace_access: Array<{
    uuid: string;
    name: string;
    role: "MANAGER" | "EDITOR" | "VIEWER";
    access_type: "organization_admin" | "direct_member";
  }>;
  accessible_workspaces_count: number;
}

interface PendingInvitation {
  invitation_uuid: string;
  email: string;
  organization_role: "admin" | "member";
  status: "pending";
  invited_at: string;
  invited_by: string;
  invited_by_email: string;
  expires_at: string;
  message?: string;
  workspace_assignments: Array<{
    workspace_uuid: string;
    workspace_name: string;
    role: "manager" | "viewer";
  }>;
  will_have_access_to_all_workspaces: boolean;
}

interface OrganizationMembersPageProps {
  initialMembers: OrganizationMember[];
  initialPendingInvitations: PendingInvitation[];
}

export function OrganizationMembersPage({
  initialMembers,
  initialPendingInvitations
}: OrganizationMembersPageProps) {
  const { selectedOrganization } = useUserStore();

  if (!selectedOrganization) {
    return (
      <div className="flex items-center justify-center p-8">
        <p className="text-muted-foreground">
          Please select an organization to view members
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-7xl mx-auto p-6">
      <OrganizationMembersManagement
        initialMembers={initialMembers}
        initialPendingInvitations={initialPendingInvitations}
      />
    </div>
  );
}