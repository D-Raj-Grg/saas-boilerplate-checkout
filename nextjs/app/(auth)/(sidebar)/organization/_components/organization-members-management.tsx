"use client";

import React, { useState, useCallback } from "react";
import { useUserStore } from "@/stores/user-store";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { DeleteConfirmationDialog } from "@/components/ui/delete-confirmation-dialog";
import {
  MoreVertical,
  Shield,
  User,
  Trash2,
  ShieldUser,
  Building2,
  UserPlus,
  Mail,
  Send,
} from "lucide-react";
import {
  getOrganizationMembersAction,
  removeOrganizationMemberAction,
} from "@/actions/organization";
import { resendInvitationAction, deleteInvitationAction } from "@/actions/invitation";
import { toast } from "sonner";
import { NewMemberInvitationDialog } from "../../workspace/_components/new-member-invitation-dialog";

interface WorkspaceAccess {
  uuid: string;
  name: string;
  role: "MANAGER" | "EDITOR" | "VIEWER";
  access_type: "organization_admin" | "direct_member";
}

interface OrganizationMember {
  uuid: string;
  name: string;
  email: string;
  organization_role: "owner" | "admin" | "member";
  is_owner: boolean;
  joined_at: string;
  workspace_access: WorkspaceAccess[];
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

interface OrganizationMembersResponse {
  current_members: OrganizationMember[];
  pending_invitations: PendingInvitation[];
  summary?: any;
}

interface OrganizationMembersManagementProps {
  initialMembers?: OrganizationMember[];
  initialPendingInvitations?: PendingInvitation[];
}

export function OrganizationMembersManagement({
  initialMembers,
  initialPendingInvitations
}: OrganizationMembersManagementProps) {
  const { userData } = useUserStore();
  const [members, setMembers] = useState<OrganizationMember[]>(initialMembers || []);
  const [pendingInvitations, setPendingInvitations] = useState<PendingInvitation[]>(initialPendingInvitations || []);
  const [loading, setLoading] = useState(!initialMembers);
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
  const [editRoleDialog, setEditRoleDialog] = useState<{
    isOpen: boolean;
    member: OrganizationMember | null;
  }>({
    isOpen: false,
    member: null,
  });
  const [inviteMemberDialog, setInviteMemberDialog] = useState(false);
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

  const loadMembers = useCallback(async () => {
    try {
      setLoading(true);
      const result = await getOrganizationMembersAction();

      if (result.success) {
        const data: OrganizationMembersResponse = result.data;
        setMembers(data.current_members || []);
        setPendingInvitations(data.pending_invitations || []);
      } else {
        toast.error("Failed to load organization members");
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setLoading(false);
    }
  }, []);

  React.useEffect(() => {
    if (!initialMembers) {
      loadMembers();
    }
  }, [initialMembers, loadMembers]);

  const handleDeleteMemberClick = (memberUuid: string, memberName: string) => {
    setDeleteMemberDialog({
      isOpen: true,
      isLoading: false,
      memberUuid,
      memberName,
    });
  };

  const handleConfirmDeleteMember = async () => {
    const { memberUuid, memberName } = deleteMemberDialog;
    setDeleteMemberDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const result = await removeOrganizationMemberAction(memberUuid);

      if (result.success) {
        toast.success(`${memberName} has been removed from the organization`);
        setDeleteMemberDialog({ isOpen: false, isLoading: false, memberUuid: '', memberName: '' });
        await loadMembers();
      } else {
        toast.error(result.error || "Failed to remove member");
        setDeleteMemberDialog(prev => ({ ...prev, isLoading: false }));
      }
    } catch {
      toast.error("An unexpected error occurred");
      setDeleteMemberDialog(prev => ({ ...prev, isLoading: false }));
    }
  };

  const handleEditRoleClick = (member: OrganizationMember) => {
    setEditRoleDialog({
      isOpen: true,
      member,
    });
  };

  const handleResendInvitation = async (invitationUuid: string) => {
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
  };

  const handleDeleteInvitationClick = (invitationUuid: string, invitationEmail: string) => {
    setDeleteInvitationDialog({
      isOpen: true,
      isLoading: false,
      invitationUuid,
      invitationEmail,
    });
  };

  const handleConfirmDeleteInvitation = async () => {
    const { invitationUuid, invitationEmail } = deleteInvitationDialog;
    setDeleteInvitationDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const result = await deleteInvitationAction(invitationUuid);

      if (result.success) {
        toast.success(`Invitation to ${invitationEmail} deleted successfully`);
        setDeleteInvitationDialog({ isOpen: false, isLoading: false, invitationUuid: '', invitationEmail: '' });
        await loadMembers();
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

  const getRoleColor = (role: string) => {
    switch (role.toLowerCase()) {
      case 'owner':
        return 'text-purple-600';
      case 'admin':
        return 'text-blue-600';
      case 'member':
        return 'text-green-600';
      default:
        return 'text-gray-600';
    }
  };

  const getRoleIcon = (role: string) => {
    switch (role.toLowerCase()) {
      case 'owner':
        return <ShieldUser className="h-3 w-3" />;
      case 'admin':
        return <Shield className="h-3 w-3" />;
      case 'member':
        return <User className="h-3 w-3" />;
      default:
        return <User className="h-3 w-3" />;
    }
  };

  const formatWorkspaceAccess = (member: OrganizationMember) => {
    if (member.organization_role === 'owner' || member.organization_role === 'admin') {
      return `All workspaces (${member.accessible_workspaces_count})`;
    }
    return `${member.accessible_workspaces_count} workspace${member.accessible_workspaces_count !== 1 ? 's' : ''}`;
  };

  const formatPendingWorkspaceAccess = (invitation: PendingInvitation) => {
    if (invitation.will_have_access_to_all_workspaces) {
      return "All workspaces";
    }
    const count = invitation.workspace_assignments.length;
    return `${count} workspace${count !== 1 ? 's' : ''}`;
  };

  const isCurrentUser = (email: string) => {
    return email === userData?.user?.email;
  };

  const canManageMember = (member: OrganizationMember) => {
    // Owners cannot be managed
    if (member.is_owner) return false;
    // Users cannot manage themselves
    if (isCurrentUser(member.email)) return false;
    return true;
  };

  if (loading) {
    return (
      <div className="space-y-4">
        <div className="border rounded-lg p-6">
          <div className="text-center text-muted-foreground">
            Loading organization members...
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
      <div>
        <h1 className="text-2xl font-bold">Organization Members</h1>
        <p className="text-muted-foreground mt-1">
          Manage members and their roles within your organization.
        </p>
      </div>
        <Button
          onClick={() => setInviteMemberDialog(true)}
        >
          <UserPlus className="mr-2 h-4 w-4" />
          Invite Member
        </Button>
      </div>
      {/* Members Table */}
      <div className="bg-white rounded-lg border">

        <div className="border-b px-6 py-3 bg-gray-50">
          <div className="grid grid-cols-4 gap-4 text-sm font-medium text-muted-foreground">
            <div>Member</div>
            <div>Role</div>
            <div>Workspace Access</div>
            <div className="text-right">Actions</div>
          </div>
        </div>

        <div className="divide-y">
          {members.length === 0 ? (
            <div className="px-6 py-8 text-center text-muted-foreground">
              No members found
            </div>
          ) : (
            members.map((member) => (
              <div key={member.uuid} className="px-6 py-4">
                <div className="grid grid-cols-4 gap-4 items-center">
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

                  <div>
                    <Badge
                      variant="outline"
                      className={`font-medium capitalize !bg-white ${getRoleColor(member.organization_role)} flex items-center gap-1`}
                    >
                      {getRoleIcon(member.organization_role)}
                      {member.organization_role}
                    </Badge>
                  </div>

                  <div className="text-sm text-muted-foreground">
                    <div className="flex items-center gap-1">
                      <Building2 className="h-3 w-3" />
                      {formatWorkspaceAccess(member)}
                    </div>
                  </div>

                  <div className="flex justify-end">
                    {canManageMember(member) && (
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon" className="h-8 w-8">
                            <MoreVertical className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem
                            onClick={() => handleEditRoleClick(member)}
                          >
                            <Shield className="mr-2 h-4 w-4" />
                            Edit Role & Access
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            className="text-red-600"
                            onClick={() => handleDeleteMemberClick(member.uuid, member.name)}
                          >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Remove from Organization
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    )}
                  </div>
                </div>
              </div>
            ))
          )}

          {/* Pending Invitations */}
          {pendingInvitations.length > 0 && (
            <>
              <div className="px-6 py-3 bg-gray-50 border-t">
                <h3 className="text-sm font-medium text-gray-700">Pending Invitations</h3>
              </div>
              {pendingInvitations.map((invitation) => (
                <div key={invitation.invitation_uuid} className="px-6 py-4 border-t">
                  <div className="grid grid-cols-4 gap-4 items-center">
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

                    <div>
                      <Badge
                        variant="outline"
                        className={`font-medium capitalize !bg-white ${getRoleColor(invitation.organization_role)} flex items-center gap-1`}
                      >
                        {getRoleIcon(invitation.organization_role)}
                        {invitation.organization_role}
                      </Badge>
                    </div>

                    <div className="text-sm text-muted-foreground">
                      <div className="flex items-center gap-1">
                        <Building2 className="h-3 w-3" />
                        {formatPendingWorkspaceAccess(invitation)}
                      </div>
                    </div>

                    <div className="flex justify-end">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon" className="h-8 w-8">
                            <MoreVertical className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem
                            onClick={() => handleResendInvitation(invitation.invitation_uuid)}
                          >
                            <Send className="mr-2 h-4 w-4" />
                            Resend Invitation
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            className="text-red-600"
                            onClick={() => handleDeleteInvitationClick(invitation.invitation_uuid, invitation.email)}
                          >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete Invitation
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
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
        description={`Are you sure you want to remove "${deleteMemberDialog.memberName}" from this organization? They will lose access to all organization resources.`}
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

      {/* Edit Role Dialog */}
      {editRoleDialog.member && (
        <NewMemberInvitationDialog
          open={editRoleDialog.isOpen}
          onOpenChange={(open) => !open && setEditRoleDialog({ isOpen: false, member: null })}
          onInviteSent={loadMembers}
          currentWorkspaceId={undefined}
          existingMember={{
            email: editRoleDialog.member.email,
            name: editRoleDialog.member.name,
            currentRole: editRoleDialog.member.organization_role,
            currentWorkspaceAccess: editRoleDialog.member.workspace_access.map(ws => ({
              workspace_id: ws.uuid,
              workspace_name: ws.name,
              role: ws.role.toLowerCase() as "manager" | "viewer",
            })),
            userUuid: editRoleDialog.member.uuid,
          }}
        />
      )}

      {/* Invite Member Dialog */}
      <NewMemberInvitationDialog
        open={inviteMemberDialog}
        onOpenChange={setInviteMemberDialog}
        onInviteSent={loadMembers}
        currentWorkspaceId={undefined}
      />
    </div>
  );
}