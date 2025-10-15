"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Building, Settings, ArrowLeftRight, Plus } from "lucide-react";
import { useUserStore } from "@/stores/user-store";
import { RequiresPermission } from "@/components/requires-permission";
import { UserOrganization, UserWorkspace } from "@/interfaces/user";
import { setCurrentWorkspaceAction } from "@/actions/user-preferences";
 
import { CreateWorkspaceDialog } from "./create-workspace-dialog";
import { toast } from "sonner";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import Link from "next/link";

interface OrganizationSwitcherDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function OrganizationSwitcherDialog({
  open,
  onOpenChange
}: OrganizationSwitcherDialogProps) {
  const router = useRouter();
  const [createWorkspaceOpen, setCreateWorkspaceOpen] = useState(false);

  const {
    userData,
    selectedOrganization,
    selectedWorkspace,
    setSelectedWorkspace,
  } = useUserStore();

  // Get plan name for an organization
  const getPlanName = (org: UserOrganization): string => {
    return org.current_plan?.name || "Unknown";
  };

  // Get plan styling based on plan name
  const getPlanStyling = (planName: string) => {
    switch (planName.toLowerCase()) {
      case 'free':
        return "bg-gray-50 text-gray-600 border-gray-200";
      case 'pro':
        return "bg-blue-50 text-blue-600 border-blue-200";
      case 'business':
        return "bg-purple-50 text-purple-600 border-purple-200";
      case 'enterprise':
        return "bg-green-50 text-green-600 border-green-200";
      default:
        return "bg-gray-50 text-gray-500 border-gray-200";
    }
  };

  async function handleWorkspaceSelect(workspace: UserWorkspace) {
    setSelectedWorkspace(workspace);

    try {
      const result = await setCurrentWorkspaceAction(workspace.uuid);

      if (!result.success) {
        toast.error("Failed to switch workspace: " + result.error);
      } else {
        toast.success("Workspace switched successfully");
        onOpenChange(false);
        window.location.href = '/dashboard';
      }
    } catch {
      toast.error("An unexpected error occurred");
    }
  }


  const availableOrganizations = selectedOrganization ? [selectedOrganization] : [];

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent className="sm:max-w-lg max-h-[80vh] overflow-hidden p-4" showCloseButton={false}>
          <DialogHeader className="hidden">
            <DialogTitle>Switch Organization & Workspace</DialogTitle>
          </DialogHeader>

          <div className="space-y-2 max-h-60 overflow-y-auto">
            {availableOrganizations.map((org) => {
              const workspaces = (userData?.workspaces || []).filter(w => w.organization_uuid === org.uuid);

              return (
                <div key={org.uuid} className="space-y-1">
                  {/* Organization Row */}
                  <div className={cn(
                    "flex w-full items-center justify-between transition-all duration-200 hover:bg-muted"
                  )}>
                    <Link
                      href="/organization"
                      className="flex items-center gap-1 flex-1 text-left "
                    >
                      <div className={cn(
                        "h-10 w-10 rounded-lg flex items-center justify-center text-muted-foreground "
                      )}>
                        <Building className="h-5 w-5" />
                      </div>
                      <div className="flex-1">
                        <div className="font-medium text-foreground">{org.name}</div>
                        <div className="text-sm text-muted-foreground">
                          {org.is_owner ? "Owner" : "Member"}
                        </div>
                      </div>
                    </Link>

                    <div className="flex items-center gap-2 pr-2.5">
                      {/* Plan Badge */}
                      {(() => {
                        const planName = getPlanName(org);
                        return (
                          <div className={`px-2.5 py-1 text-xs font-medium rounded-md border ${getPlanStyling(planName)}`}>
                            {planName}
                          </div>
                        );
                      })()}

                      
                    </div>
                  </div>
                  {/* Workspaces list (flat) */}
                  {workspaces.length > 0 && (
                    <div className="ml-6 space-y-1">
                      {workspaces.map((workspace) => {
                        const isCurrentWorkspace = selectedWorkspace?.uuid === workspace.uuid;

                        return (
                          <button
                            key={workspace.uuid}
                            onClick={() => handleWorkspaceSelect(workspace)}
                            className={cn(
                              "flex w-full items-center gap-3 rounded-md p-2 text-left transition-all duration-200 hover:bg-muted text-muted-foreground hover:text-foreground"
                            )}
                          >
                            <div className={cn(
                              "h-6 w-6 rounded-full border flex items-center justify-center text-xs font-medium flex-shrink-0",
                              isCurrentWorkspace
                                ? "border-primary text-primary bg-primary/5"
                                : "border-border"
                            )}>
                              {workspace.name.charAt(0)}
                            </div>
                            <div className="flex-1">
                              <span className="font-medium">{workspace.name}</span>
                              {workspace.description && (
                                <div className="text-xs text-muted-foreground truncate">
                                  {workspace.description}
                                </div>
                              )}
                            </div>
                            {isCurrentWorkspace && (
                              <div className="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full">
                                Current
                              </div>
                            )}
                          </button>
                        );
                      })}
                    </div>
                  )}
                </div>
              );
            })}
          </div>

          <div className="border-t pt-4 space-y-1">
            {/* Switch Organization - New dedicated page */}

            <RequiresPermission orgPermission="can_create_workspaces">
              <Button
                onClick={() => setCreateWorkspaceOpen(true)}
                variant="ghost"
                className="w-full justify-start"
              >
                <Plus className="h-4 w-4 text-muted-foreground" />
                <span className="font-medium text-foreground">Add New Workspace</span>
              </Button>
            </RequiresPermission>

            <Button
              onClick={() => {
                onOpenChange(false);
                router.push('/organizations');
              }}
              variant="ghost"
              className="w-full justify-start"
            >
              <ArrowLeftRight className="h-4 w-4 text-muted-foreground" />
              <span className="font-medium text-foreground">Switch Organization</span>
            </Button>


            <RequiresPermission orgPermission="can_manage_organization">
              <Button
                onClick={() => {
                  onOpenChange(false);
                  router.push('/organization');
                }}
                variant="ghost"
                className="w-full justify-start"
              >
                <Settings className="h-4 w-4 text-muted-foreground" />
                <span className="font-medium text-foreground">Organization Settings</span>
              </Button>
            </RequiresPermission>
          </div>
        </DialogContent>
      </Dialog>

      {/* Create Workspace Dialog */}
      <CreateWorkspaceDialog
        open={createWorkspaceOpen}
        onOpenChange={setCreateWorkspaceOpen}
        targetOrganization={selectedOrganization || null}
      />
    </>
  );
}