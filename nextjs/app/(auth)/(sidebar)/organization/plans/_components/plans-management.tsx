"use client";

import { useMemo, useState } from "react";
import { useUserStore } from "@/stores/user-store";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { CreditCard } from "lucide-react";
import { OrganizationStats } from "@/interfaces";
import { getCustomerDashboardUrlAction } from "@/actions/plans";
import { toast } from "sonner";

interface PlansManagementProps {
  organizationStats: OrganizationStats | null;
}

export function PlansManagement({ organizationStats }: PlansManagementProps) {
  const { selectedOrganization } = useUserStore();
  const [isLoading, setIsLoading] = useState(false);

  const plan = useMemo(() => {
    return organizationStats?.plan || {
      name: 'Free',
      type: 'Free',
      validity: 'Lifetime',
      endDate: new Date().toLocaleDateString(),
    };
  }, [organizationStats]);

  const handleManageBilling = async () => {
    setIsLoading(true);
    try {
      const result = await getCustomerDashboardUrlAction();

      if (result.success && result.dashboardUrl) {
        window.location.href = result.dashboardUrl;
      } else {
        toast.error(result.error || "Failed to open billing dashboard");
      }
    } catch {
      setIsLoading(false);
      toast.error("An unexpected error occurred");
    } finally {

    }
  };

  if (!selectedOrganization) {
    return (
      <div className="p-6">
        <div className="text-center text-muted-foreground">
          Please select an organization to view details
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-7xl mx-auto p-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">Plans & Subscriptions</h1>
          <p className="text-muted-foreground mt-1">
            Manage your organization&apos;s subscription and billing information.
          </p>
        </div>
        <Button onClick={handleManageBilling} disabled={isLoading}>
          <CreditCard className="h-4 w-4 mr-2" />
          {isLoading ? "Loading..." : "Manage Billing"}
        </Button>
      </div>

      {/* Plans & Subscriptions Section */}
      <div className="space-y-6">

        <Card className="py-0">
          <CardContent className="p-0">
            <div className="border-b px-6 py-4">
              <div className="grid grid-cols-4 gap-4 text-sm font-medium text-muted-foreground">
                <div>Plan Name</div>
                <div>Type</div>
                <div>Validity</div>
                <div>Start Date</div>
              </div>
            </div>

            <div className="px-6 py-4">
              <div className="grid grid-cols-4 gap-4 items-center">
                <div className="flex items-center gap-3">
                  <div className="shrink-0 size-8 rounded-full bg-gray-100 flex items-center justify-center text-sm font-medium">
                    {plan.name.charAt(0)}
                  </div>
                  <span className="font-medium text-foreground">{plan.name}</span>
                </div>
                <div className="text-foreground">Subscription</div>
                <div className="text-foreground">Annualy</div>
                <div className="text-foreground">{new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toLocaleDateString()}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}