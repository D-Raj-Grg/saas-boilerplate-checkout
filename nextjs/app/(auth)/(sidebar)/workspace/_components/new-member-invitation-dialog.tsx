"use client";

import React, { useState, useCallback } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { createOrganizationInvitationAction } from "@/actions/invitation";
import { changeOrganizationMemberRoleAction } from "@/actions/organization";
import { getWorkspacesAction } from "@/actions/workspace";
import { toast } from "sonner";
import { Workspace } from "@/interfaces/organization";

const inviteSchema = z.object({
  email: z.string().email("Please enter a valid email address"),
  role: z.enum(["member", "admin"]),
  workspace_assignments: z.array(z.object({
    workspace_id: z.string(),
    workspace_name: z.string(),
    has_edit_permission: z.boolean(),
  })).optional(),
});

type InviteFormValues = z.infer<typeof inviteSchema>;

interface ExistingMember {
  email: string;
  name: string;
  currentRole: "owner" | "admin" | "member";
  currentWorkspaceAccess: Array<{
    workspace_id: string;
    workspace_name: string;
    role: "manager" | "viewer";
  }>;
  userUuid: string;
}

interface NewMemberInvitationDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onInviteSent?: () => void;
  currentWorkspaceId?: string;
  existingMember?: ExistingMember;
  restrictToCurrentWorkspace?: boolean;
}

export function NewMemberInvitationDialog({
  open,
  onOpenChange,
  onInviteSent,
  currentWorkspaceId,
  existingMember,
  restrictToCurrentWorkspace = false,
}: NewMemberInvitationDialogProps) {
  const [isLoading, setIsLoading] = useState(false);
  const [workspaces, setWorkspaces] = useState<Workspace[]>([]);
  const [workspacesLoaded, setWorkspacesLoaded] = useState(false);

  const isEditMode = !!existingMember;

  const form = useForm<InviteFormValues>({
    resolver: zodResolver(inviteSchema),
    defaultValues: {
      email: existingMember?.email || "",
      role: existingMember?.currentRole === "admin" ? "admin" : "member",
      workspace_assignments: existingMember?.currentWorkspaceAccess?.map(ws => ({
        workspace_id: ws.workspace_id,
        workspace_name: ws.workspace_name,
        has_edit_permission: ws.role === "manager",
      })) || [],
    },
  });

  const selectedRole = form.watch("role");
  const workspaceAssignments = form.watch("workspace_assignments") || [];

  const loadWorkspaces = useCallback(async () => {
    try {
      setWorkspacesLoaded(false);

      // If restricted to current workspace only, load only that workspace
      if (restrictToCurrentWorkspace && currentWorkspaceId) {
        const result = await getWorkspacesAction();
        if (result.success) {
          // Filter to only show the current workspace
          const currentWorkspace = result.data?.find((w: Workspace) => w.uuid === currentWorkspaceId);
          setWorkspaces(currentWorkspace ? [currentWorkspace] : []);
        } else {
          setWorkspaces([]);
        }
      } else {
        // Load all workspaces (for admins/owners)
        const result = await getWorkspacesAction();
        if (result.success) {
          setWorkspaces(result.data || []);
        } else {
          setWorkspaces([]);
        }
      }
    } catch {
      setWorkspaces([]);
    } finally {
      setWorkspacesLoaded(true);
    }
  }, [restrictToCurrentWorkspace, currentWorkspaceId]);

  // Load workspaces when dialog opens
  React.useEffect(() => {
    if (open) {
      loadWorkspaces();
    } else {
      // Reset state when dialog closes
      setWorkspacesLoaded(false);
      setWorkspaces([]);
    }
  }, [open, loadWorkspaces]);

  // Pre-select current workspace for members
  React.useEffect(() => {
    if (selectedRole === "member" && currentWorkspaceId && workspaces.length > 0) {
      const currentWorkspace = workspaces.find(w => w.uuid === currentWorkspaceId);
      if (currentWorkspace && workspaceAssignments.length === 0) {
        form.setValue("workspace_assignments", [{
          workspace_id: currentWorkspace.uuid,
          workspace_name: currentWorkspace.name,
          has_edit_permission: false,
        }]);
      }
    }
  }, [selectedRole, currentWorkspaceId, workspaces, workspaceAssignments.length, form]);

  // Clear workspace assignments when switching to admin
  React.useEffect(() => {
    if (selectedRole === "admin") {
      form.setValue("workspace_assignments", []);
    }
  }, [selectedRole, form]);

  function toggleWorkspaceSelection(workspace: Workspace) {
    const current = workspaceAssignments || [];
    const existing = current.find(w => w.workspace_id === workspace.uuid);

    if (existing) {
      // Remove workspace
      const updated = current.filter(w => w.workspace_id !== workspace.uuid);
      form.setValue("workspace_assignments", updated);
    } else {
      // Add workspace
      const updated = [...current, {
        workspace_id: workspace.uuid,
        workspace_name: workspace.name,
        has_edit_permission: false,
      }];
      form.setValue("workspace_assignments", updated);
    }
  }

  function updateWorkspacePermission(hasEditPermission: boolean) {
    const current = workspaceAssignments || [];
    const updated = current.map(w => ({ ...w, has_edit_permission: hasEditPermission }));
    form.setValue("workspace_assignments", updated);
  }

  async function onSubmit(data: InviteFormValues) {
    try {
      setIsLoading(true);

      // Validate member role has at least one workspace
      if (data.role === "member" && (!data.workspace_assignments || data.workspace_assignments.length === 0)) {
        toast.error("Members must be assigned to at least one workspace");
        return;
      }

      if (isEditMode && existingMember) {
        // Edit existing member
        const roleData = {
          role: data.role,
          ...(data.role === "member" && data.workspace_assignments && {
            workspace_assignments: data.workspace_assignments.map(w => ({
              workspace_id: w.workspace_id,
              role: (w.has_edit_permission ? "manager" : "viewer") as "manager" | "viewer",
            })),
          }),
        };

        const result = await changeOrganizationMemberRoleAction(existingMember.userUuid, roleData);

        if (result.success) {
          toast.success("Member role and access updated successfully");
          form.reset();
          onOpenChange(false);
          onInviteSent?.();
        } else {
          toast.error(result.error || "Failed to update member");
        }
      } else {
        // Create new invitation
        const invitationData = {
          email: data.email,
          role: data.role,
          ...(data.role === "member" && data.workspace_assignments && {
            workspace_assignments: data.workspace_assignments.map(w => ({
              workspace_id: w.workspace_id,
              role: (w.has_edit_permission ? "manager" : "viewer") as "manager" | "viewer",
            })),
          }),
        };

        const result = await createOrganizationInvitationAction(invitationData);

        if (result.success) {
          const action = result.data?.action;
          if (action === "updated") {
            toast.success("User updated and added to new workspaces");
          } else {
            toast.success("Invitation sent successfully");
          }
          form.reset();
          onOpenChange(false);
          onInviteSent?.();
        } else {
          toast.error(result.error || "Failed to send invitation");
        }
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setIsLoading(false);
    }
  }

  const isFormValid = () => {
    const email = form.watch("email");
    const role = form.watch("role");
    const assignments = form.watch("workspace_assignments") || [];

    if (!email) return false;
    if (role === "member" && assignments.length === 0) return false;
    return true;
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader className="flex flex-row items-center justify-between">
          <DialogTitle>{isEditMode ? "Edit Member Role & Access" : "New Member Invitation"}</DialogTitle>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            <FormField
              control={form.control}
              name="email"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Email</FormLabel>
                  <FormControl>
                    <Input
                      placeholder="Email address"
                      readOnly={isEditMode}
                      className={isEditMode ? "bg-gray-50" : ""}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="role"
              render={({ field }) => (
                <FormItem className="space-y-3">
                  <FormControl>
                    <RadioGroup
                      onValueChange={field.onChange}
                      value={field.value}
                      className="space-y-4"
                    >
                      <div className="flex items-start space-x-3">
                        <RadioGroupItem value="member" id="member" className="mt-1" />
                        <div className="space-y-1">
                          <FormLabel htmlFor="member" className="text-sm font-medium">
                            Member
                          </FormLabel>
                          <p className="text-sm text-muted-foreground">
                            Members are not able to view or edit organization settings, and they only have access to workspaces they are assigned to.
                          </p>
                        </div>
                      </div>

                      <div className="flex items-start space-x-3">
                        <RadioGroupItem
                          value="admin"
                          id="admin"
                          className="mt-1"
                          disabled={restrictToCurrentWorkspace}
                        />
                        <div className="space-y-1">
                          <FormLabel
                            htmlFor="admin"
                            className={`text-sm font-medium ${restrictToCurrentWorkspace ? 'text-muted-foreground' : ''}`}
                          >
                            Admin
                          </FormLabel>
                          <p className="text-sm text-muted-foreground">
                            Admins are able to view and edit all organization settings, and they have full permissions to all workspaces.
                            {restrictToCurrentWorkspace && (
                              <span className="block mt-1 text-xs text-red-600">
                                Only organization admins can invite other admins.
                              </span>
                            )}
                          </p>
                        </div>
                      </div>
                    </RadioGroup>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {selectedRole === "member" && (
              <div className="space-y-4">
                <div>
                  <FormLabel className="text-sm font-medium">Workspaces</FormLabel>
                  {restrictToCurrentWorkspace && (
                    <p className="text-xs text-muted-foreground mt-1">
                      You can only invite members to your current workspace.
                    </p>
                  )}
                  <div className="relative">
                    <div className="mt-2 max-h-60 overflow-y-auto border rounded-md" style={{ scrollbarWidth: "thin" }}>
                      {!workspacesLoaded ? (
                        <div className="p-4 text-center text-sm text-muted-foreground">
                          Loading workspaces...
                        </div>
                      ) : workspaces.length === 0 ? (
                        <div className="p-4 text-center text-sm text-muted-foreground">
                          No workspaces found
                        </div>
                      ) : (
                        workspaces.map((workspace) => {
                          const assignment = workspaceAssignments?.find(w => w.workspace_id === workspace.uuid);
                          const isSelected = !!assignment;
                          const isCurrentWorkspace = workspace.uuid === currentWorkspaceId;
                          const isDisabled = restrictToCurrentWorkspace || isCurrentWorkspace;

                          return (
                            <div
                              key={workspace.uuid}
                              className={`p-3 border-b last:border-b-0 ${isSelected ? "bg-blue-50" : ""
                                }`}
                            >
                              <div className="flex items-center space-x-2">
                                <Checkbox
                                  checked={isSelected}
                                  onCheckedChange={() => toggleWorkspaceSelection(workspace)}
                                  disabled={isDisabled}
                                />
                                <div className="flex-1">
                                  <div className="font-medium text-sm">
                                    {workspace.name}
                                    {isCurrentWorkspace && (
                                      <span className="ml-2 text-xs text-blue-600 bg-blue-100 px-2 py-0.5 rounded">
                                        Current
                                      </span>
                                    )}
                                  </div>
                                </div>
                              </div>
                            </div>
                          );
                        })
                      )}
                    </div>
                    {/* Show fade indicator if there are many workspaces */}
                    {workspaces.length > 4 && (
                      <div className="absolute bottom-0 left-0 right-0 h-4 bg-gradient-to-t from-white to-transparent pointer-events-none rounded-b-md" />
                    )}
                  </div>
                  <p className="text-xs text-muted-foreground mt-2">
                    When this invitation is accepted, the user will be added to these workspaces.
                  </p>
                </div>
                {workspaceAssignments && workspaceAssignments.length > 0 && (
                  <div className="space-y-2">
                    <div className="flex items-center space-x-2">
                      <Checkbox
                        checked={workspaceAssignments.every(w => w.has_edit_permission)}
                        onCheckedChange={(checked) => {
                          updateWorkspacePermission(!!checked);
                        }}
                      />
                      <div>
                        <FormLabel className="text-sm font-medium">Workspace Edit Permission</FormLabel>
                        <p className="text-xs text-muted-foreground">
                          Grants full editing permission for all workspaces they are assigned to – update workspace settings, purge test data, etc.
                        </p>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            )}

            {selectedRole === "admin" && (
              <div className="p-3 bg-blue-50 border border-blue-200 rounded-md">
                <p className="text-sm text-blue-800">
                  Automatically gets access to all workspaces with full permissions.
                </p>
              </div>
            )}

            <DialogFooter>
              <Button
                type="submit"
                disabled={isLoading || !isFormValid()}
              >
                {isLoading
                  ? (isEditMode ? "Updating..." : "Sending...")
                  : (isEditMode ? "Update Member" : "Send Invitation")
                }
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}