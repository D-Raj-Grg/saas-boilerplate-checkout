"use client";

import React from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { updateWorkspaceAction, deleteWorkspaceAction } from "@/actions/workspace";
import { loadUserData } from "@/lib/auth-client";
import { WorkspaceDetailsSection } from "./sections/workspace-details";
import { DangerZoneSection } from "./sections/danger-zone";

interface UnifiedSettingsFormProps {
  initialWorkspace?: any; // Workspace data from API
}

export function UnifiedSettingsForm({
  initialWorkspace,
}: UnifiedSettingsFormProps) {
  const router = useRouter();

  // Handler for workspace details updates
  const handleWorkspaceDetailsSave = async (data: {
    name?: string;
    description?: string;
  }): Promise<boolean> => {
    try {
      const result = await updateWorkspaceAction(data);

      if (result.success) {
        toast.success("Workspace updated", {
          description: "Workspace details have been saved.",
        });
        await loadUserData(); // Refresh workspace data in store
        router.refresh();
        return true;
      } else {
        toast.error("Update failed", {
          description: result.error || "Failed to update workspace details",
        });
        return false;
      }
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred",
      });
      return false;
    }
  };

  // Handler for workspace deletion
  const handleWorkspaceDelete = async (): Promise<boolean> => {
    try {
      const result = await deleteWorkspaceAction();

      if (result.success) {
        toast.success("Workspace deleted", {
          description: "Workspace has been permanently deleted.",
        });
        await loadUserData(); // Refresh workspace data
        router.push('/dashboard'); // Redirect to dashboard
        return true;
      } else {
        toast.error("Delete failed", {
          description: result.error || "Failed to delete workspace",
        });
        return false;
      }
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred",
      });
      return false;
    }
  };

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Workspace Settings</h1>
          <p className="text-muted-foreground">
            Manage your workspace details and settings
          </p>
        </div>
      </div>

      <div className="space-y-0">
        {/* Workspace Details */}
        <WorkspaceDetailsSection
          initialData={{
            name: initialWorkspace?.name,
            description: initialWorkspace?.description,
          }}
          onSave={handleWorkspaceDetailsSave}
          autoSave={false}
        />

        {/* Danger Zone */}
        <DangerZoneSection
          onDeleteWorkspace={handleWorkspaceDelete}
        />
      </div>
    </div>
  );
}
