"use client";

import { useState, useEffect, useCallback } from "react";
import { useUserStore } from "@/stores/user-store";
import { usePermissions } from "@/hooks/use-permissions";
import { RequiresPermission } from "@/components/requires-permission";
import { Button } from "@/components/ui/button";
import { PlanGatedFeature } from "@/components/plan-gated-feature";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { ConfirmationDialog } from "@/components/ui/confirmation-dialog";
import {
  UserPlus,
  MoreVertical,
  Mail,
  Send,
  User,
  ShieldUser,
  Trash2,
  CheckCircle,
  XCircle
} from "lucide-react";
import { getWorkspaceMembersAction, removeWorkspaceMemberAction, changeWorkspaceMemberRoleAction } from "@/actions/workspace";
import { resendInvitationAction, deleteInvitationAction } from "@/actions/invitation";
import { toast } from "sonner";
import { NewMemberInvitationDialog } from "./new-member-invitation-dialog";
import { Badge } from "@/components/ui/badge";
import { DeleteConfirmationDialog } from "@/components/ui/delete-confirmation-dialog";

interface WorkspaceMember {
  uuid: string;
  name: string;
  first_name: string;
  last_name: string;
  email: string;
  email_verified_at: string;
  created_at: string;
  updated_at: string;
  role: string; // workspace role
  joined_at: string;
  is_org_admin_access: boolean;
  organization_role: string;
}

interface WorkspaceInvitation {
  uuid: string | null;
  name: string | null;
  first_name: string | null;
  last_name: string | null;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  role: string; // workspace role
  joined_at: string | null;
  is_org_admin_access: boolean;
  organization_role: string;
  invitation_status: string;
  invitation_uuid: string;
  expires_at: string;
  message: string | null;
  invited_by: {
    name: string;
    email: string;
  };
  workspace_assignments: {
    workspace_uuid: string;
    workspace_name: string;
    role: string;
  }[];
  will_have_access_to_all_workspaces: boolean;
}

interface WorkspaceMembersResponse {
  current_members: WorkspaceMember[];
  pending_invitations: WorkspaceInvitation[];
  summary: {
    total_current_members: number;
    total_pending_invitations: number;
    total_members_including_pending: number;
  };
}

interface WorkspaceManagementProps {
  initialData?: WorkspaceMembersResponse;
}

export function WorkspaceManagement({
  initialData
}: WorkspaceManagementProps) {

  const { selectedWorkspace, userData, isInitialized, selectedOrganization } = useUserStore();
  const { canChangeUserRoles, canRemoveUsers } = usePermissions();

  // Check if user is an organization member (not admin/owner)
  // Organization members should only be able to invite to current workspace
  const isOrgMember = Boolean(
    selectedOrganization &&
    !selectedOrganization.is_owner &&
    !userData?.current_organization_permissions?.can_manage_organization
  );

  const [members, setMembers] = useState<WorkspaceMember[]>(initialData?.current_members || []);
  const [invitations, setInvitations] = useState<WorkspaceInvitation[]>(initialData?.pending_invitations || []);
  const [loading, setLoading] = useState(!initialData);
  const [deleteMemberDialog, setDeleteMemberDialog] = useState<{
    isOpen: boolean;
    isLoading: boolean;
    memberUuid: string;
    memberName: string;
  }>({
    isOpen: false,
    isLoading: false,
    memberUuid: '',
    memberName: '',
  });
  const [deleteInvitationDialog, setDeleteInvitationDialog] = useState<{
    isOpen: boolean;
    isLoading: boolean;
    invitationUuid: string;
    invitationEmail: string;
  }>({
    isOpen: false,
    isLoading: false,
    invitationUuid: '',
    invitationEmail: '',
  });
  const [changeRoleDialog, setChangeRoleDialog] = useState<{
    isOpen: boolean;
    isLoading: boolean;
    memberUuid: string;
    memberName: string;
    newRole: string;
    currentRole: string;
  }>({
    isOpen: false,
    isLoading: false,
    memberUuid: '',
    memberName: '',
    newRole: '',
    currentRole: '',
  });
  const [newInvitationDialog, setNewInvitationDialog] = useState(false);

  const loadMembersAndInvitations = useCallback(async () => {
    if (!selectedWorkspace) return;

    try {
      setLoading(true);

      const result = await getWorkspaceMembersAction();

      if (result.success && result.data) {
        setMembers(result.data.current_members || []);
        setInvitations(result.data.pending_invitations || []);
      } else {
        toast.error(result.error || "Failed to load workspace members");
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setLoading(false);
    }
  }, [selectedWorkspace]);

  useEffect(() => {
    // Only load if no initial data was provided and have a workspace selected
    if (selectedWorkspace && !initialData) {
      loadMembersAndInvitations();
    }
  }, [selectedWorkspace, loadMembersAndInvitations, initialData]);

  const handleDeleteMemberClick = (memberUuid: string, memberName: string) => {
    setDeleteMemberDialog({
      isOpen: true,
      isLoading: false,
      memberUuid,
      memberName,
    });
  };

  const handleConfirmDeleteMember = async () => {
    if (!selectedWorkspace) return;

    const { memberUuid, memberName } = deleteMemberDialog;
    setDeleteMemberDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const result = await removeWorkspaceMemberAction(memberUuid);

      if (result.success) {
        toast.success(`${memberName} has been removed from the workspace`);
        setDeleteMemberDialog({ isOpen: false, isLoading: false, memberUuid: '', memberName: '' });
        await loadMembersAndInvitations();
      } else {
        toast.error(result.error || "Failed to remove member");
        setDeleteMemberDialog(prev => ({ ...prev, isLoading: false }));
      }
    } catch {
      toast.error("An unexpected error occurred");
      setDeleteMemberDialog(prev => ({ ...prev, isLoading: false }));
    }
  };


  function handleChangeRoleClick(memberUuid: string, newRole: string, memberName: string, currentRole: string) {
    setChangeRoleDialog({
      isOpen: true,
      isLoading: false,
      memberUuid,
      memberName,
      newRole,
      currentRole,
    });
  }

  async function handleConfirmChangeRole() {
    if (!selectedWorkspace) return;

    const { memberUuid, memberName, newRole } = changeRoleDialog;
    setChangeRoleDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const result = await changeWorkspaceMemberRoleAction(memberUuid, newRole);

      if (result.success) {
        toast.success(`${memberName}'s role has been updated to ${newRole}`);
        setChangeRoleDialog({ isOpen: false, isLoading: false, memberUuid: '', memberName: '', newRole: '', currentRole: '' });
        await loadMembersAndInvitations();
      } else {
        toast.error(result.error || "Failed to update role");
        setChangeRoleDialog(prev => ({ ...prev, isLoading: false }));
      }
    } catch {
      toast.error("An unexpected error occurred");
      setChangeRoleDialog(prev => ({ ...prev, isLoading: false }));
    }
  }

  async function handleResendInvitation(invitationUuid: string) {
    if (!selectedWorkspace) return;

    try {
      const result = await resendInvitationAction(invitationUuid);

      if (result.success) {
        toast.success("Invitation resent successfully");
      } else {
        toast.error(result.error || "Failed to resend invitation");
      }
    } catch {
      toast.error("An unexpected error occurred");
    }
  }

  const handleDeleteInvitationClick = (invitationUuid: string, invitationEmail: string) => {
    setDeleteInvitationDialog({
      isOpen: true,
      isLoading: false,
      invitationUuid,
      invitationEmail,
    });
  };

  const handleConfirmDeleteInvitation = async () => {
    if (!selectedWorkspace) return;

    const { invitationUuid, invitationEmail } = deleteInvitationDialog;
    setDeleteInvitationDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const result = await deleteInvitationAction(invitationUuid);

      if (result.success) {
        toast.success(`Invitation to ${invitationEmail} deleted successfully`);
        setDeleteInvitationDialog({ isOpen: false, isLoading: false, invitationUuid: '', invitationEmail: '' });
        await loadMembersAndInvitations();
      } else {
        toast.error(result.error || "Failed to delete invitation");
        setDeleteInvitationDialog(prev => ({ ...prev, isLoading: false }));
      }
    } catch {
      toast.error("An unexpected error occurred");
      setDeleteInvitationDialog(prev => ({ ...prev, isLoading: false }));
    }
  };

  const getInitials = (name: string, email: string) => {
    if (name && name.trim()) {
      const parts = name.trim().split(' ');
      if (parts.length >= 2) {
        return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
      }
      return name.substring(0, 2).toUpperCase();
    }
    return email.substring(0, 2).toUpperCase();
  };

  const getRoleColor = (role: string | undefined) => {
    if (!role) return 'text-gray-600';
    switch (role.toLowerCase()) {
      case 'owner':
        return 'text-green-600';
      case 'admin':
        return 'text-blue-600';
      case 'member':
        return 'text-yellow-600';
      default:
        return 'text-gray-600';
    }
  };

  // Let Next.js route-level loading.tsx handle the loading state during initialization
  if (!isInitialized || loading) {
    return null;
  }

  // Only show this message if user is fully initialized but somehow has no workspace
  // This should rarely happen in normal flow since logged-in users should always have a workspace
  if (!selectedWorkspace) {
    return (
      <div className="p-6">
        <div className="text-center text-muted-foreground">
          Please select a workspace to manage members
        </div>
      </div>
    );
  }

  const getOrganizationRoleLabel = (role: string) => {
    switch (role) {
      case 'owner': return 'Organization Owner';
      case 'admin': return 'Organization Admin';
      case 'member': return 'Workspace Member';
      default: return role;
    }
  };

  const canEdit = (member: WorkspaceMember) => {
    // Org owners and admins can always edit
    if (member.organization_role === 'owner' || member.organization_role === 'admin') return true;
    // For members, check their workspace role
    if (member.role === 'manager' || member.role === 'editor') return true;
    return false;
  };

  const canInvitationEdit = (invitation: WorkspaceInvitation) => {
    // Org admins can always edit
    if (invitation.organization_role === 'admin') return true;
    // For members, check their workspace assignment role
    if (invitation.workspace_assignments && invitation.workspace_assignments.length > 0) {
      return invitation.workspace_assignments.some(wa => wa.role === 'manager' || wa.role === 'editor');
    }
    return false;
  };

  // Sort members by organization role priority
  const getRolePriority = (role: string) => {
    switch (role.toLowerCase()) {
      case 'owner': return 1;
      case 'admin': return 2;
      case 'member': return 3;
      default: return 4;
    }
  };

  const sortedMembers = [...members].sort((a, b) => {
    return getRolePriority(a.organization_role) - getRolePriority(b.organization_role);
  });

  const sortedInvitations = [...invitations].sort((a, b) => {
    return getRolePriority(a.organization_role) - getRolePriority(b.organization_role);
  });

  return (
    <div className="mx-auto space-y-6 max-w-7xl">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <h1 className="text-2xl font-bold text-gray-900">{selectedWorkspace.name}</h1>
          {selectedWorkspace.description && (
            <p className="mt-1 text-sm text-gray-600">{selectedWorkspace.description}</p>
          )}
        </div>
        <div className="flex items-center gap-2">
          {selectedWorkspace && (
            <RequiresPermission workspacePermission="can_invite_users">
              <PlanGatedFeature feature="team_members">
                <Button
                  onClick={() => setNewInvitationDialog(true)}
                  className=""
                >
                  <UserPlus className="mr-0.5 h-4 w-4" />
                  Invite Member
                </Button>
              </PlanGatedFeature>
            </RequiresPermission>
          )}
        </div>
      </div>
      {/* Members Table */}
      <div className="bg-white rounded-md border">
        <div className="border-b px-6 py-4">
          <div className="grid grid-cols-3 gap-4 text-sm font-medium text-muted-foreground">
            <div>Name</div>
            <div className="text-center">Can Edit</div>
            <div className="text-right">Role</div>
          </div>
        </div>

        <div className="divide-y">
          {sortedMembers.length === 0 && !loading ? (
            <div className="px-6 py-8 text-center text-muted-foreground">
              No members found
            </div>
          ) : (
            sortedMembers.map((member) => (
              <div key={member.uuid} className="px-6 py-4">
                <div className="grid grid-cols-3 gap-4 items-center">
                  <div className="flex items-center gap-3">
                    <Avatar className="h-10 w-10">
                      <AvatarFallback className="bg-gray-100 text-gray-700 font-medium">
                        {getInitials(member.name, member.email)}
                      </AvatarFallback>
                    </Avatar>
                    <div>
                      <div className="font-medium">{member.name}</div>
                      <div className="text-sm text-muted-foreground">{member.email}</div>
                    </div>
                  </div>
                  <div className="flex items-center justify-center">
                    {canEdit(member) ? (
                      <CheckCircle className="h-5 w-5 text-green-600" />
                    ) : (
                      <XCircle className="h-5 w-5 text-red-600" />
                    )}
                  </div>
                  <div className="flex items-center justify-end gap-2">
                    <Badge variant={"outline"} className={`font-medium capitalize !bg-white ${getRoleColor(member.organization_role)}`}>
                      {getOrganizationRoleLabel(member.organization_role)}
                    </Badge>
                    {(canChangeUserRoles || canRemoveUsers) && !member.is_org_admin_access && (
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon" className="h-8 w-8">
                            <MoreVertical className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          {/* Check if this is the current user */}
                          {member.email === userData?.user?.email ? (
                            <DropdownMenuItem
                              disabled
                              className="text-muted-foreground"
                            >
                              <User className="mr-0.5 h-4 w-4" />
                              Cannot manage own account
                            </DropdownMenuItem>
                          ) : (
                            <>
                              {/* Role change options - only show if user has can_change_user_roles permission */}
                              <RequiresPermission workspacePermission="can_change_user_roles">
                                {/* Only show role options that are different from current role */}
                                {/* Don't allow demoting owners without special handling */}
                                {!member.is_org_admin_access && (
                                  <>
                                    {member.role?.toLowerCase() !== 'manager' && (
                                      <DropdownMenuItem
                                        key="make-manager"
                                        onClick={() => handleChangeRoleClick(member.uuid, 'manager', member.name, member.role || '')}
                                      >
                                        <ShieldUser className="mr-0.5 h-4 w-4" />
                                        Add Edit Access
                                      </DropdownMenuItem>
                                    )}
                                    {member.role?.toLowerCase() !== 'viewer' && (
                                      <DropdownMenuItem
                                        key="make-viewer"
                                        onClick={() => handleChangeRoleClick(member.uuid, 'viewer', member.name, member.role || '')}
                                      >
                                        <User className="mr-0.5 h-4 w-4" />
                                        Remove Edit Access
                                      </DropdownMenuItem>
                                    )}

                                    {/* Remove option - only show if user has can_remove_users permission */}
                                    <RequiresPermission workspacePermission="can_remove_users">
                                      <DropdownMenuItem
                                        key="remove"
                                        className="text-red-600"
                                        onClick={() => handleDeleteMemberClick(member.uuid, member.name)}
                                      >
                                        <Trash2 className="mr-0.5 h-4 w-4" />
                                        Remove
                                      </DropdownMenuItem>
                                    </RequiresPermission>
                                  </>
                                )}
                              </RequiresPermission>
                            </>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    )}
                  </div>
                </div>
              </div>
            ))
          )}

          {/* Pending Invitations */}
          {sortedInvitations.filter(inv => inv.invitation_status !== 'cancelled').length > 0 && (
            <>
              <div className="px-6 py-3 bg-gray-50">
                <h3 className="text-sm font-medium text-gray-700">Pending Invitations</h3>
              </div>
              {sortedInvitations.filter(invitation => invitation.invitation_status !== 'cancelled').map((invitation) => (
                <div key={invitation.invitation_uuid} className="px-6 py-4">
                  <div className="grid grid-cols-3 gap-4 items-center">
                    <div className="flex items-center gap-3">
                      <Avatar className="h-10 w-10">
                        <AvatarFallback className="bg-gray-100 text-gray-700 font-medium">
                          <Mail className="h-5 w-5" />
                        </AvatarFallback>
                      </Avatar>
                      <div>
                        <div className="font-medium">Guest</div>
                        <div className="text-sm text-muted-foreground">{invitation.email}</div>
                      </div>
                    </div>
                    <div className="flex items-center justify-center">
                      {canInvitationEdit(invitation) ? (
                        <CheckCircle className="h-5 w-5 text-green-600" />
                      ) : (
                        <XCircle className="h-5 w-5 text-red-600" />
                      )}
                    </div>
                    <div className="flex items-center justify-end gap-2">
                      <Badge variant={"outline"} className={`font-medium capitalize !bg-white ${getRoleColor(invitation.organization_role)}`}>
                        {getOrganizationRoleLabel(invitation.organization_role)}
                      </Badge>
                      <RequiresPermission workspacePermission="can_invite_users">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-8 w-8">
                              <MoreVertical className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem
                              key="resend"
                              onClick={() => handleResendInvitation(invitation.invitation_uuid)}
                            >
                              <Send className="mr-0.5 h-4 w-4" />
                              Resend Invitation
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              key="delete"
                              className="text-red-600"
                              onClick={() => handleDeleteInvitationClick(invitation.invitation_uuid, invitation.email)}
                            >
                              <Trash2 className="mr-0.5 h-4 w-4" />
                              Delete Invitation
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </RequiresPermission>
                    </div>
                  </div>
                </div>
              ))}
            </>
          )}
        </div>
      </div>


      {/* Delete Member Confirmation */}
      <DeleteConfirmationDialog
        open={deleteMemberDialog.isOpen}
        onOpenChange={(open) => !open && setDeleteMemberDialog({ isOpen: false, isLoading: false, memberUuid: '', memberName: '' })}
        title="Remove Member"
        description={`Are you sure you want to remove "${deleteMemberDialog.memberName}" from this workspace? They will lose access to all workspace resources.`}
        confirmText="Remove Member"
        onConfirm={handleConfirmDeleteMember}
        isLoading={deleteMemberDialog.isLoading}
      />

      {/* Delete Invitation Confirmation */}
      <DeleteConfirmationDialog
        open={deleteInvitationDialog.isOpen}
        onOpenChange={(open) => !open && setDeleteInvitationDialog({ isOpen: false, isLoading: false, invitationUuid: '', invitationEmail: '' })}
        title="Delete Invitation"
        description={`Are you sure you want to delete the invitation to "${deleteInvitationDialog.invitationEmail}"? This action cannot be undone and the invitation will no longer be valid.`}
        confirmText="Delete Invitation"
        onConfirm={handleConfirmDeleteInvitation}
        isLoading={deleteInvitationDialog.isLoading}
      />

      {/* Change Role Confirmation */}
      <ConfirmationDialog
        open={changeRoleDialog.isOpen}
        onOpenChange={(open) => !open && setChangeRoleDialog({ isOpen: false, isLoading: false, memberUuid: '', memberName: '', newRole: '', currentRole: '' })}
        title="Change Member Role"
        description={
          changeRoleDialog.newRole === 'owner'
            ? `Are you sure you want to make "${changeRoleDialog.memberName}" an owner? Owners have full control over the workspace, including the ability to delete it and manage all members.`
            : changeRoleDialog.newRole === 'admin'
              ? `Are you sure you want to make "${changeRoleDialog.memberName}" an admin? Admins can manage members and workspace settings.`
              : `Are you sure you want to change "${changeRoleDialog.memberName}" to a member? They will have limited access to workspace resources.`
        }
        confirmText={`Make ${changeRoleDialog.newRole?.charAt(0).toUpperCase()}${changeRoleDialog.newRole?.slice(1)}`}
        onConfirm={handleConfirmChangeRole}
        isLoading={changeRoleDialog.isLoading}
        variant="default"
        loadingText="Updating role..."
      />

      {/* New Member Invitation Dialog */}
      <NewMemberInvitationDialog
        open={newInvitationDialog}
        onOpenChange={setNewInvitationDialog}
        onInviteSent={loadMembersAndInvitations}
        currentWorkspaceId={selectedWorkspace?.uuid}
        restrictToCurrentWorkspace={isOrgMember}
      />
    </div>
  );
}