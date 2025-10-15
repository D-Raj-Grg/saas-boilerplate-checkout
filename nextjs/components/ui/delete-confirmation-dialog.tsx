"use client";

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";

interface DeleteConfirmationDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: string;
  confirmText?: string;
  onConfirm: () => void;
  isLoading?: boolean;
  variant?: "destructive" | "default";
}

export function DeleteConfirmationDialog({
  open,
  onOpenChange,
  title,
  description,
  confirmText = "Delete",
  onConfirm,
  isLoading = false,
  variant = "destructive",
}: DeleteConfirmationDialogProps) {
  const handleConfirm = () => {
    onConfirm();
    // Don't close the dialog here - let the parent handle it after the action completes
  };

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{title}</AlertDialogTitle>
          <AlertDialogDescription>{description}</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel disabled={isLoading}>Cancel</AlertDialogCancel>
          <AlertDialogAction
            onClick={handleConfirm}
            disabled={isLoading}
            className={
              variant === "destructive"
                ? "bg-destructive text-destructive-foreground hover:bg-destructive/90"
                : "bg-primary text-primary-foreground hover:bg-primary/90"
            }
          >
            {isLoading ? "Processing..." : confirmText}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}