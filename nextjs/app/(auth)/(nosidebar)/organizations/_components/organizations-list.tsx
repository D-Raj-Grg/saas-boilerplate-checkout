"use client";

import { useRouter } from "next/navigation";
import { useUserStore } from "@/stores/user-store";
import { UserOrganization } from "@/interfaces/user";
import { Building, Plus, User, Crown } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useState } from "react";
import { cn } from "@/lib/utils";
import { CreateOrganizationDialog } from "@/app/(auth)/(sidebar)/organization/_components/create-organization-dialog";
import { Logo } from "@/components/ui/logo";

export function OrganizationsList() {
  const router = useRouter();
  const { userData, selectedOrganization } = useUserStore();
  const [createOrgOpen, setCreateOrgOpen] = useState(false);

  const organizations = userData?.organizations || [];

  // Get plan name for an organization
  const getPlanName = (org: UserOrganization): string => {
    return org.current_plan?.name || "Free";
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

  const handleOrganizationSelect = (org: UserOrganization) => {
    // Navigate to workspaces view for this organization
    router.push(`/organizations?org_id=${org.uuid}`);
  };

  const getWorkspaceCount = (orgUuid: string): number => {
    return userData?.workspaces?.filter(w => w.organization_uuid === orgUuid).length || 0;
  };

  if (organizations.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div className="max-w-md w-full text-center">
          {/* Logo */}
          <div className="flex justify-center mb-8 h-8 w-auto mx-auto">
            <Logo />
          </div>

          <div className="mb-6">
            <Building className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h2 className="text-2xl font-semibold text-foreground mb-2">No Organizations</h2>
            <p className="text-muted-foreground">
              You don&apos;t have any organizations yet. Create your first organization to get started.
            </p>
          </div>
          <Button onClick={() => setCreateOrgOpen(true)} className="w-full">
            <Plus className="h-4 w-4 mr-2" />
            Create Organization
          </Button>
          <CreateOrganizationDialog
            open={createOrgOpen}
            onOpenChange={setCreateOrgOpen}
            showTrigger={false}
          />
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 flex justify-center items-center">
      <div className="max-w-2xl mx-auto p-6 pt-12">
        {/* Logo */}
        <div className="flex justify-center mb-8 h-8 w-auto mx-auto">
          <Logo />
        </div>

        {/* Header */}
        <div className="text-center mb-8">
          <h1 className="text-3xl font-semibold text-foreground mb-2">
            Your Organizations
          </h1>
          <p className="text-lg text-muted-foreground">
            Select which organization you want to continue with, or create a new one.
          </p>
        </div>

        {/* Organizations List */}
        <div className="space-y-4 mb-6">
          {organizations.map((org) => {
            const isCurrentOrg = selectedOrganization?.uuid === org.uuid;
            const planName = getPlanName(org);
            const workspaceCount = getWorkspaceCount(org.uuid);

            return (
              <button
                key={org.uuid}
                onClick={() => handleOrganizationSelect(org)}
                className={cn(
                  "w-full p-6 bg-white rounded-lg border text-left transition-all duration-200 hover:shadow-md hover:border-primary/40"
                )}
              >
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-4 flex-1">
                    <div className={cn(
                      "h-12 w-12 rounded-lg flex items-center justify-center",
                      isCurrentOrg
                        ? "bg-primary/10 text-primary"
                        : "bg-muted text-muted-foreground"
                    )}>
                      <Building className="h-6 w-6" />
                    </div>

                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <h3 className="text-lg font-semibold text-foreground">
                          {org.name}
                        </h3>
                        {isCurrentOrg && (
                          <div className="px-2 py-0.5 bg-primary/10 text-primary text-xs font-medium rounded-full">
                            Current
                          </div>
                        )}
                      </div>

                      <div className="flex items-center gap-4 text-sm text-muted-foreground">
                        <div className="flex items-center gap-1">
                          {org.is_owner ? (
                            <>
                              <Crown className="h-3 w-3" />
                              <span>Owner</span>
                            </>
                          ) : (
                            <>
                              <User className="h-3 w-3" />
                              <span>Member</span>
                            </>
                          )}
                        </div>

                        <span>•</span>

                        <span>
                          {workspaceCount} workspace{workspaceCount !== 1 ? 's' : ''}
                        </span>
                      </div>

                      {(() => {
                        const description = (org as any)?.description as string | undefined;
                        return description ? (
                          <p className="text-sm text-muted-foreground mt-2 line-clamp-2">
                            {description}
                          </p>
                        ) : null;
                      })()}
                    </div>
                  </div>

                  {/* Plan Badge */}
                  <div className={`px-3 py-1.5 text-xs font-medium rounded-md border ${getPlanStyling(planName)}`}>
                    {planName}
                  </div>
                </div>
              </button>
            );
          })}
        </div>

        {/* Create New Organization Button */}
        <Button
          onClick={() => setCreateOrgOpen(true)}
          className="w-full h-12 text-base font-medium"
          size="lg"
        >
          <Plus className="h-4 w-4 mr-2" />
          New Organization
        </Button>

        {/* Footer */}
        <div className="mt-8 pt-6 border-t text-center text-sm text-muted-foreground">
          <p>
            You&apos;re logged in as <span className="font-medium">{userData?.user?.email}</span>
          </p>
          <div className="mt-2">
            <span>Need to manage or delete your user account?</span>{" "}
            <button className="text-primary hover:underline">Edit Profile</button>
          </div>
        </div>
      </div>

      {/* Create Organization Dialog */}
      <CreateOrganizationDialog
        open={createOrgOpen}
        onOpenChange={setCreateOrgOpen}
        showTrigger={false}
      />
    </div>
  );
}