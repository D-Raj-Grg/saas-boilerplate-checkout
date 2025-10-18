"use client";

import { useMemo } from "react";
import { useRouter } from "next/navigation";
import { useUserStore } from "@/stores/user-store";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { CreditCard, Crown } from "lucide-react";
import { OrganizationStats } from "@/interfaces";
import { formatCurrency } from "@/lib/currency";

interface PlansManagementProps {
  organizationStats: OrganizationStats | null;
}

export function PlansManagement({ organizationStats }: PlansManagementProps) {
  const router = useRouter();
  const { selectedOrganization } = useUserStore();

  // Get all active plans, or show free plan if none
  const activePlans = useMemo(() => {
    if (organizationStats?.all_plans && organizationStats.all_plans.length > 0) {
      return organizationStats.all_plans;
    }
    // Return free plan as default
    return [{
      uuid: 'free',
      name: 'Free',
      slug: 'free',
      features: { priority_support: false },
      limits: {},
      price: '0',
      formatted_price: 'Free',
      purchased_at: undefined,
      started_at: undefined,
      status: 'active',
    }];
  }, [organizationStats]);

  // Get current (highest priority) plan
  const currentPlan = organizationStats?.plan;

  const handleManageBilling = () => {
    // Redirect to pricing page to upgrade/downgrade plan
    router.push('/pricing');
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
        <Button onClick={handleManageBilling}>
          <CreditCard className="h-4 w-4 mr-2" />
          Manage Billing
        </Button>
      </div>

      {/* Plans & Subscriptions Section */}
      <div className="space-y-6">
        <Card className="py-0">
          <CardContent className="p-0">
            {/* Table Header */}
            <div className="border-b px-6 py-4 bg-muted/50">
              <div className="grid grid-cols-6 gap-4 text-sm font-medium text-muted-foreground">
                <div>Plan Name</div>
                <div>Price</div>
                <div>Billing Cycle</div>
                <div>Purchased Date</div>
                <div>Status</div>
                <div>Currency</div>
              </div>
            </div>

            {/* Table Rows - All Active Plans */}
            {activePlans.map((plan, index) => {
              const isCurrentPlan = currentPlan?.uuid === plan.uuid;

              return (
                <div
                  key={plan.uuid}
                  className={`px-6 py-4 ${index !== activePlans.length - 1 ? 'border-b' : ''}`}
                >
                  <div className="grid grid-cols-6 gap-4 items-center">
                    {/* Plan Name with Icon */}
                    <div className="flex items-center gap-3">
                      
                      <div className="flex items-center gap-2">
                        <span className="font-medium text-foreground">{plan.name}</span>
                        {isCurrentPlan && (
                          <Badge variant="default" className="bg-teal-600 hover:bg-teal-700 text-white flex items-center gap-1">
                            <Crown className="h-3 w-3" />
                            Active
                          </Badge>
                        )}
                      </div>
                    </div>

                    {/* Price */}
                    <div className="text-foreground font-medium">
                      {plan.formatted_price || formatCurrency(parseFloat(plan.price), organizationStats?.organization.currency || 'NPR')}
                    </div>

                    {/* Billing Cycle */}
                    <div className="text-foreground capitalize">
                      {plan.slug.includes('yearly') ? 'Yearly' : plan.slug.includes('monthly') ? 'Monthly' : 'Lifetime'}
                    </div>

                    {/* Purchased Date */}
                    <div className="text-foreground text-sm">
                      {plan.purchased_at
                        ? new Date(plan.purchased_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                          })
                        : 'N/A'}
                    </div>

                    {/* Status */}
                    <div>
                      <Badge variant="outline" className="border-green-200 bg-green-50 text-green-700">
                        Active
                      </Badge>
                    </div>

                    {/* Currency */}
                    <div className="text-foreground font-mono text-sm">
                      {organizationStats?.organization.currency || 'NPR'}
                    </div>
                  </div>
                </div>
              );
            })}
          </CardContent>
        </Card>

        {/* Info Message */}
        {activePlans.length > 1 && (
          <div className="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <Crown className="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" />
            <div>
              <p className="text-sm text-blue-900 font-medium">
                Multiple Active Plans
              </p>
              <p className="text-sm text-blue-700 mt-1">
                Your organization has {activePlans.length} active plans. The plan marked as &quot;Active&quot; with the crown icon is your current highest-priority plan, which determines your feature access and limits.
              </p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}