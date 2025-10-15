"use client";

import {  useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";
import { createOrganizationAction } from "@/actions/organization";
import { loadUserData } from "@/lib/auth-client";
import { toast } from "sonner";

const createOrgSchema = z.object({
  name: z.string().min(1, "Organization name is required"),
  description: z.string().optional(),
  workspace_name: z.string().optional(),
  workspace_description: z.string().optional(),
});

type CreateOrgForm = z.infer<typeof createOrgSchema>;

interface CreateOrganizationDialogProps {
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  showTrigger?: boolean;
}

export function CreateOrganizationDialog({ open: controlledOpen, onOpenChange, showTrigger = true }: CreateOrganizationDialogProps = {}) {
  const [uncontrolledOpen, setUncontrolledOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const handleOpenChange = (newOpen: boolean) => {
    if (controlledOpen === undefined) {
      setUncontrolledOpen(newOpen);
    }
    onOpenChange?.(newOpen);
  };

  const open = controlledOpen ?? uncontrolledOpen;

  const form = useForm<CreateOrgForm>({
    resolver: zodResolver(createOrgSchema),
    defaultValues: {
      name: "",
      description: "",
      workspace_name: "",
      workspace_description: "",
    },
  });

  async function onSubmit(data: CreateOrgForm) {
    setIsLoading(true);
    try {
      const result = await createOrganizationAction(data);

      if (result.success) {
        toast.success("Organization created successfully");
        handleOpenChange(false);
        form.reset();
        // Refresh user data to get updated organizations
        await loadUserData();
      } else {
        toast.error(result.error || "Failed to create organization");
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      {showTrigger && (
        <DialogTrigger asChild>
          <Button variant={"ghost"} className="w-full justify-start">
            <Plus className="h-4 w-4 text-muted-foreground" />
            <span className="font-medium text-foreground">Create new Organization</span>
          </Button>
        </DialogTrigger>
      )}
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Create Organization</DialogTitle>
          <DialogDescription>
            Create a new organization to manage your projects and team.
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Organization Name</FormLabel>
                  <FormControl>
                    <Input placeholder="Enter organization name" autoFocus required {...field} />
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
                    <Textarea placeholder="Describe your organization" rows={3} {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="workspace_name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Initial Workspace Name (Optional)</FormLabel>
                  <FormControl>
                    <Input placeholder="Default workspace name" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <div className="flex justify-end gap-3">
              <Button type="button" variant="outline" onClick={() => handleOpenChange(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={isLoading || !form.watch("name")}>
                {isLoading ? "Creating..." : (
                  <>
                    <Plus className="mr-0.5 h-4 w-4" />
                    Create Organization
                  </>
                )}
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}