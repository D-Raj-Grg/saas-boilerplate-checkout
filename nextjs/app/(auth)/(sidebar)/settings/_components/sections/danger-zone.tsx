"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { DeleteConfirmationDialog } from "@/components/ui/delete-confirmation-dialog";
import { AlertTriangle, Trash2 } from "lucide-react";
import { useUserStore } from "@/stores/user-store";

interface DangerZoneSectionProps {
  onDeleteWorkspace: () => Promise<boolean>;
}

export function DangerZoneSection({
  onDeleteWorkspace,
}: DangerZoneSectionProps) {
  const { selectedWorkspace } = useUserStore();
  const [deleteDialog, setDeleteDialog] = useState({
    isOpen: false,
    isLoading: false,
  });

  const handleDeleteClick = () => {
    setDeleteDialog({ isOpen: true, isLoading: false });
  };

  const handleConfirmDelete = async () => {
    setDeleteDialog(prev => ({ ...prev, isLoading: true }));

    try {
      const success = await onDeleteWorkspace();
      if (success) {
        setDeleteDialog({ isOpen: false, isLoading: false });
      } else {
        setDeleteDialog(prev => ({ ...prev, isLoading: false }));
      }
    } catch {
      setDeleteDialog(prev => ({ ...prev, isLoading: false }));
    }
  };

  const handleCloseDialog = () => {
    if (!deleteDialog.isLoading) {
      setDeleteDialog({ isOpen: false, isLoading: false });
    }
  };

  return (
    <>
      <div id="danger-zone" className="grid grid-cols-1 lg:grid-cols-3 gap-8 py-8">
        {/* Left Column - Title & Description */}
        <div className="lg:col-span-1">
          <div className="flex items-center gap-3 mb-3">
            <AlertTriangle className="h-5 w-5 text-red-600" />
            <h2 className="text-lg font-semibold text-red-900">Danger Zone</h2>
          </div>
          <p className="text-sm text-muted-foreground">
            Irreversible and destructive actions that will permanently affect your workspace.
          </p>


        </div>

        {/* Right Column - Danger Actions */}
        <div className="lg:col-span-2 space-y-6">
          <div className="p-0 ">
            <div className="space-y-4">
              <div>
                <h3 className="font-semibold text-red-900 mb-2">Delete Workspace</h3>
                <p className="text-sm text-red-800 mb-4">
                  Once you delete this workspace, there is no going back. This action cannot be undone and will permanently delete the workspace &quot;{selectedWorkspace?.name}&quot; and all of its data.
                </p>
              </div>

              <Button
                onClick={handleDeleteClick}
                variant="destructive"
                className="bg-red-600 hover:bg-red-700 text-white"
              >
                <Trash2 className="h-4 w-4 mr-2" />
                Delete Workspace
              </Button>
            </div>
          </div>

        </div>
      </div>

      {/* Delete Confirmation Dialog */}
      <DeleteConfirmationDialog
        open={deleteDialog.isOpen}
        onOpenChange={handleCloseDialog}
        title="Delete Workspace"
        description={`Are you sure you want to delete "${selectedWorkspace?.name}"? This action cannot be undone and will permanently remove the workspace and all its data, including data and member access.`}
        confirmText="Delete Workspace"
        onConfirm={handleConfirmDelete}
        isLoading={deleteDialog.isLoading}
      />
    </>
  );
}