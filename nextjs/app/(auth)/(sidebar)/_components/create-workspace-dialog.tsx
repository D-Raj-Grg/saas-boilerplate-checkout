"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { PlanGatedFeature } from "@/components/plan-gated-feature";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { createWorkspaceAction } from "@/actions/workspace";
import { toast } from "sonner";
import { useUserStore } from "@/stores/user-store";
import { loadUserData } from "@/lib/auth-client";
import { Plus } from "lucide-react";

const formSchema = z.object({
  name: z.string().min(1, "Workspace name is required").max(100),
  description: z.string().optional(),
});

type FormValues = z.infer<typeof formSchema>;

interface CreateWorkspaceDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  targetOrganization?: { uuid: string; name: string } | null;
  onWorkspaceCreated?: () => void;
}

export function CreateWorkspaceDialog({ open, onOpenChange, targetOrganization, onWorkspaceCreated }: CreateWorkspaceDialogProps) {
  const [isLoading, setIsLoading] = useState(false);
  const { selectedOrganization } = useUserStore();

  // Use target organization if provided, otherwise use selected organization
  const organizationToUse = targetOrganization || selectedOrganization;

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      description: "",
    },
  });

  async function onSubmit(data: FormValues) {
    if (!organizationToUse) {
      toast.error("Please select an organization first");
      return;
    }

    try {
      setIsLoading(true);

      const result = await createWorkspaceAction({
        name: data.name,
        description: data.description,
        organization_uuid: organizationToUse.uuid,
      });

      if (result.success) {
        toast.success("Workspace created successfully");
        form.reset();
        onOpenChange(false);

        // Refresh user data to get the new workspace
        await loadUserData();

        // Call the callback to notify parent component
        if (onWorkspaceCreated) {
          onWorkspaceCreated();
        }
      } else {
        toast.error(result.error || "Failed to create workspace");
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Create Workspace</DialogTitle>
          <DialogDescription>
            Create a new workspace in {organizationToUse?.name || "your organization"}
          </DialogDescription>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Workspace Name</FormLabel>
                  <FormControl>
                    <Input placeholder="Enter workspace name" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Description (Optional)</FormLabel>
                  <FormControl>
                    <Textarea
                      placeholder="Enter workspace description"
                      className="resize-none"
                      rows={3}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <div className="flex justify-end space-x-2 pt-4">
              <Button
                type="button"
                variant="outline"
                onClick={() => onOpenChange(false)}
                disabled={isLoading}
              >
                Cancel
              </Button>
              <PlanGatedFeature
                feature="workspaces"
              >
                <Button type="submit" disabled={isLoading}>
                  {isLoading ? "Creating..." : (
                    <>
                      <Plus className="mr-0.5 h-4 w-4" />
                      Create Workspace
                    </>
                  )}
                </Button>
              </PlanGatedFeature>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}