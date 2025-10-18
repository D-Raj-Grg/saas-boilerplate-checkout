"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { toast } from "sonner";
import { useUserStore } from "@/stores/user-store";
import { updateOrganizationAction } from "@/actions/organization";
import { loadUserData } from "@/lib/auth-client";
import { Settings, AlertTriangle, Banknote } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { TooltipChild } from "@/components/ui/tooltip-child";
import { OrganizationStats } from "@/interfaces";
import { getCurrencySymbol } from "@/lib/currency";

interface OrganizationOverviewProps {
  organizationStats: OrganizationStats | null;
}

const organizationDetailsSchema = z.object({
  organizationName: z
    .string()
    .min(1, "Organization name is required")
    .min(3, "Organization name must be at least 3 characters"),
});

type OrganizationDetailsForm = z.infer<typeof organizationDetailsSchema>;

export function OrganizationOverview({ organizationStats }: OrganizationOverviewProps) {
  const { selectedOrganization } = useUserStore();
  const [isLoading, setIsLoading] = useState(false);

  // Check if organization has any paid plans
  const hasPaidPlan = organizationStats?.plan && organizationStats.plan.slug !== 'free';

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<OrganizationDetailsForm>({
    resolver: zodResolver(organizationDetailsSchema),
    defaultValues: {
      organizationName: selectedOrganization?.name || "Divyashwar's Organization",
    },
  });

  const onSubmit = async (data: OrganizationDetailsForm) => {
    if (!selectedOrganization?.uuid) {
      toast.error("Error", {
        description: "No organization selected",
      });
      return;
    }

    setIsLoading(true);
    try {
      const result = await updateOrganizationAction(selectedOrganization.uuid, {
        name: data.organizationName.trim(),
      });

      if (result.success) {
        toast.success("Organization updated", {
          description: "Organization name has been saved successfully.",
        });

        // Refresh user data to update the organization name in the store
        await loadUserData();
      } else {
        toast.error("Update failed", {
          description: result.error || "Failed to update organization name",
        });
      }
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred while saving",
      });
    } finally {
      setIsLoading(false);
    }
  };

  if (!selectedOrganization) {
    return (
      <div className="flex items-center justify-center p-8">
        <p className="text-muted-foreground">
          Please select an organization to view details
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Organization Settings</h1>
          <p className="text-muted-foreground">
            Configure organization-level settings and manage organization details
          </p>
        </div>
      </div>

      <div className="space-y-0">
        {/* Organization Details Section */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 py-8 border-b">
          {/* Left Column - Title & Description */}
          <div className="lg:col-span-1">
            <div className="flex items-center gap-3 mb-3">
              <Settings className="h-5 w-5 text-[#005F5A]" />
              <h2 className="text-lg font-semibold">Organization Details</h2>
            </div>
            <p className="text-sm text-muted-foreground">
              Configure the basic settings for your organization.
            </p>
          </div>

          {/* Right Column - Form Controls */}
          <div className="lg:col-span-2 space-y-6">
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
              <div className="space-y-2">
                <Label htmlFor="organizationName" className="text-sm font-medium">
                  Organization Name
                </Label>
                <Input
                  id="organizationName"
                  {...register("organizationName")}
                  disabled={isLoading}
                  className="w-full"
                />
                {errors.organizationName && (
                  <p className="text-sm text-red-600">
                    {errors.organizationName.message}
                  </p>
                )}
                <p className="text-xs text-muted-foreground">
                  This name will only be displayed internally and not shown to customers.
                </p>
              </div>

              <div>
                <Button
                  type="submit"
                  disabled={isLoading}
                  className="bg-primary hover:bg-primary/90 text-white"
                >
                  {isLoading ? "Saving..." : "Save"}
                </Button>
              </div>
            </form>
          </div>
        </div>

        {/* Currency & Market Section */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 py-8 border-b">
          {/* Left Column - Title & Description */}
          <div className="lg:col-span-1">
            <div className="flex items-center gap-3 mb-3">
              <Banknote className="h-5 w-5 text-[#005F5A]" />
              <h2 className="text-lg font-semibold">Currency & Market</h2>
            </div>
            <p className="text-sm text-muted-foreground">
              Your organization&apos;s currency is set based on your first paid plan purchase.
            </p>
          </div>

          {/* Right Column - Form Controls */}
          <div className="lg:col-span-2 space-y-6">
            <div className="space-y-2">
              <Label htmlFor="currency" className="text-sm font-medium">
                Currency
              </Label>
              <div className="flex items-center gap-3">
                <Input
                  id="currency"
                  value={`${organizationStats?.organization.currency || 'NPR'} (${getCurrencySymbol(organizationStats?.organization.currency || 'NPR')})`}
                  disabled
                  className="w-full bg-muted cursor-not-allowed"
                />
                {!hasPaidPlan && (
                  <TooltipChild content="Currency will be set when you purchase your first paid plan">
                    <span className="text-xs text-muted-foreground whitespace-nowrap">
                      Default
                    </span>
                  </TooltipChild>
                )}
              </div>
              <p className="text-xs text-muted-foreground">
                {hasPaidPlan
                  ? "Currency is locked after purchasing a paid plan and cannot be changed."
                  : "Default currency is NPR (Nepalese Rupee). This will be updated when you purchase a plan."}
              </p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="market" className="text-sm font-medium">
                Market
              </Label>
              <Input
                id="market"
                value={organizationStats?.organization.market || 'nepal'}
                disabled
                className="w-full bg-muted cursor-not-allowed capitalize"
              />
              <p className="text-xs text-muted-foreground">
                Market determines which payment gateways are available for your organization.
              </p>
            </div>
          </div>
        </div>

        {/* Danger Zone Section */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 py-8">
          {/* Left Column - Title & Description */}
          <div className="lg:col-span-1">
            <div className="flex items-center gap-3 mb-3">
              <AlertTriangle className="h-5 w-5 text-red-600" />
              <h2 className="text-lg font-semibold">Danger Zone</h2>
            </div>
            <p className="text-sm text-muted-foreground">
              Transfer your organization or completely delete it. Please proceed with caution.
            </p>
          </div>

          {/* Right Column - Form Controls */}
          <div className="lg:col-span-2 space-y-8">
            {/* Transfer Ownership */}
            <div className="space-y-4">
              <div>
                <h3 className="text-base font-medium text-foreground">
                  Transfer Ownership
                </h3>
                <p className="text-sm text-muted-foreground">
                  Want to transfer ownership of this organization to a different user?
                </p>
              </div>
              <div>
                <TooltipChild content="Coming Soon">
                  <span className="inline-block">
                    <Button
                      variant="outline"
                      disabled
                      className="text-red-600 border-red-200 hover:bg-red-50"
                    >
                      Transfer Ownership
                    </Button>
                  </span>
                </TooltipChild>
              </div>
            </div>

            {/* Delete Organization */}
            <div className="space-y-4">
              <div>
                <h3 className="text-base font-medium text-foreground">
                  Delete Organization
                </h3>
                <p className="text-sm text-muted-foreground">
                  No longer using this organization? Delete it to remove all of your data.
                </p>
              </div>

              <div className="p-4 bg-amber-50 border border-amber-200 rounded-md flex items-start gap-3">
                <AlertTriangle className="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" />
                <p className="text-sm text-amber-800">
                  Organization must be on the launch plan and have no active stores before it can be deleted.
                </p>
              </div>

              <div>
                <TooltipChild content="Coming Soon">
                  <span className="inline-block">
                    <Button
                      variant="outline"
                      disabled
                      className="text-red-600 border-red-200 hover:bg-red-50"
                    >
                      Delete Organization
                    </Button>
                  </span>
                </TooltipChild>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}