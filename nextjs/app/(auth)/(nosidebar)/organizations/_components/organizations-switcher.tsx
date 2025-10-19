"use client";

import { useRouter } from "next/navigation";
import { useUserStore } from "@/stores/user-store";
import { UserOrganization } from "@/interfaces/user";
import { Building, Plus, Star } from "lucide-react";
import { Button } from "@/components/ui/button";
import { RequiresPermission } from "@/components/requires-permission";
import { useState, useMemo } from "react";
import { cn } from "@/lib/utils";
import { CreateOrganizationDialog } from "@/app/(auth)/(sidebar)/organization/_components/create-organization-dialog";
import { Logo } from "@/components/ui/logo";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { WorkspacesList } from "./workspaces-list";
import Link from "next/link";
import { logoutAction } from "@/actions/auth";
import { toast } from "sonner";
import { setCurrentOrganizationAction } from "@/actions/user-preferences";

interface OrganizationsSwitcherProps {
  orgId?: string;
}

interface Testimonial {
  rating: number;
  review: string;
  author: string;
  authorImage?: string;
}

// Testimonials data
const testimonials: Testimonial[] = [
  {
    rating: 5,
    review:
      "Very slick, very well implemented, and development seems to be being driven by real user needs which is great to see.",
    author: "Tim O'Donnell",
    authorImage: "/images/pricing/st-pricing/slim.png",
  },
  {
    rating: 5,
    review:
      "Outstanding A/B testing platform. Best one I have used. Provides reliable statistical analysis and meaningful insights. Minimal learning curve required.",
    author: "David Kim",
    authorImage: "/images/pricing/st-pricing/timothy.png",
  },
  {
    rating: 5,
    review:
      "Amazing experience! The A/B testing platform helped our startup optimize our landing page and increase sign-ups by 40%. The statistical analysis is robust.",
    author: "Emily Watson",
    authorImage: "/images/pricing/st-pricing/miriam.png",
  },
  {
    rating: 5,
    review:
      `${process.env.NEXT_PUBLIC_APP_NAME} made optimizing our SaaS conversion funnel super easy and effective! Simple to use, powerful features and game changer for data-driven teams.`,
    author: "Michael Chang",
    authorImage: "/images/pricing/st-pricing/prasad.png",
  },
];

export function OrganizationsSwitcher({ orgId }: OrganizationsSwitcherProps) {
  const router = useRouter();
  const { userData, clearUser, setSelectedOrganization } = useUserStore();
  const [createOrgOpen, setCreateOrgOpen] = useState(false);
  const [isLoading, setIsLoading] = useState<string | null>(null);

  // Random testimonial selection that changes on page refresh
  const randomTestimonial = useMemo(() => {
    return testimonials[Math.floor(Math.random() * testimonials.length)];
  }, []);

  // If orgId is provided, show workspaces for that organization
  if (orgId) {
    const selectedOrg = userData?.organizations?.find(
      (org) => org.uuid === orgId
    );
    if (!selectedOrg) {
      // Organization not found, redirect to organizations list
      return <OrganizationsSwitcher />;
    }
    return <WorkspacesList organization={selectedOrg} />;
  }

  const organizations = userData?.organizations || [];

  const handleOrganizationSelect = async (org: UserOrganization) => {
    setIsLoading(org.uuid);

    try {
      const result = await setCurrentOrganizationAction(org.uuid);

      if (result.success) {
        setSelectedOrganization(org);
        // Navigate to workspaces view for this organization
        router.push(`/organizations?org_id=${org.uuid}`);
      } else {
        toast.error("Failed to switch organization: " + result.error);
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setIsLoading(null);
    }
  };

  const handleLogout = async () => {
    await logoutAction();
    clearUser();
    toast.success("Logged out successfully", {
      description: "See you next time!",
    });
    router.push("/login");
  };

  if (organizations.length === 0) {
    return (
      <div className="min-h-screen grid grid-cols-1 lg:grid-cols-[40%_60%]">
        {/* Left Panel - Testimonial (40% width) */}
        <TestimonialPanel testimonial={randomTestimonial} />

        {/* Right Panel - No Organizations (60% width) */}
        <div className="bg-white sticky top-0 h-screen overflow-y-auto">
          <div className="flex pt-20 min-h-full p-10 justify-center">
            <div className="max-w-xl w-full text-center">
              <div className="mb-6">
                <Building className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h2 className="text-2xl font-semibold text-foreground mb-2">
                  No Organizations
                </h2>
                <p className="text-muted-foreground">
                  You don&apos;t have any organizations yet. Create your first
                  organization to get started.
                </p>
              </div>
              <Button
                onClick={() => setCreateOrgOpen(true)}
                className="w-full"
              >
                <Plus className="h-4 w-4 mr-2" />
                New Organization
              </Button>
              <CreateOrganizationDialog
                open={createOrgOpen}
                onOpenChange={setCreateOrgOpen}
                showTrigger={false}
              />
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen grid grid-cols-1 lg:grid-cols-[45%_55%]">
      {/* Left Panel - Testimonial (40% width, hidden on mobile) */}
      <TestimonialPanel testimonial={randomTestimonial} />

      {/* Right Panel - Organizations List (60% width) */}
      <div className="bg-white sticky top-0 h-screen overflow-y-auto">
        <div className="flex pt-20 min-h-full p-10 justify-start">
          <div className="max-w-xl w-full">
            {/* Header */}
            <div className="text-left mb-8">
              <h1 className="text-2xl font-semibold text-[#0A0A0A] mb-2">
                Your Organizations
              </h1>
              <p className="text-[#737373]">
                Select which organization you want to continue with, or create
                a new one.
              </p>
            </div>

            {/* Organizations List - No spacing between items */}
            <div className="mb-6 border border-[#E5E5E5] rounded-lg overflow-hidden">
              {organizations.map((org, index) => {
                // Get workspaces for this organization
                const orgWorkspaces = userData?.workspaces?.filter(
                  (workspace) => workspace.organization_uuid === org.uuid
                ) || [];

                // Create workspace names string
                const workspaceNames = orgWorkspaces.map((w) => w.name).join(", ");
                const isOrgLoading = isLoading === org.uuid;

                return (
                  <button
                    key={org.uuid}
                    onClick={() => handleOrganizationSelect(org)}
                    disabled={isOrgLoading}
                    className={cn(
                      "w-full p-4 bg-white text-left transition-all duration-200 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed",
                      index !== organizations.length - 1 &&
                      "border-b border-[#E5E5E5]"
                    )}
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex-1 min-w-0">
                        <h3 className="text-base font-medium text-[#0A0A0A] mb-1">
                          {org.name}
                        </h3>
                        {workspaceNames && (
                          <p className="text-sm text-[#737373] truncate">
                            {workspaceNames}
                          </p>
                        )}
                      </div>
                      {isOrgLoading && (
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                          <div className="animate-spin rounded-full h-4 w-4 border-2 border-primary border-t-transparent"></div>
                          <span>Switching...</span>
                        </div>
                      )}
                    </div>
                  </button>
                );
              })}
            </div>

            {/* Create New Organization Button */}
            <RequiresPermission
              orgPermission="can_create_organization"
              fallback={
                <div className="text-sm text-[#737373] text-left py-4">
                  You already have a free organization. Please{" "}
                  <Link href="/pricing" className="text-[#005F5A] hover:underline">
                    upgrade your existing organization
                  </Link>{" "}
                  or choose a paid plan.
                </div>
              }
            >
              <Button
                onClick={() => setCreateOrgOpen(true)}
                className="w-full h-12 text-base font-medium"
                size="lg"
              >
                <Plus className="h-4 w-4 mr-2" />
                New Organization
              </Button>
            </RequiresPermission>

            {/* Footer */}
            <div className="pt-6 text-left text-sm text-[#737373]">
              <p>
                You&apos;re logged in as{" "}
                <span className="font-medium text-[#0A0A0A]">
                  {userData?.user?.email || "divyashwarg@bsf.io"}
                </span>
                .{" "}
                <button
                  onClick={handleLogout}
                  className="text-[#005F5A] hover:underline"
                >
                  Logout
                </button>
              </p>
              <p className="mt-1">
                Need to manage or delete your user account?{" "}
                <button className="text-[#005F5A] hover:underline">
                  <Link href='/profile'>
                    Edit Profile
                  </Link>
                </button>
              </p>
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
    </div>
  );
}

// Testimonial Panel Component
function TestimonialPanel({ testimonial }: { testimonial: Testimonial }) {
  return (
    <div className="hidden lg:flex bg-gray-50 sticky top-0 h-screen overflow-y-auto justify-end">
      <div className="flex flex-col pt-20 p-8 w-full max-w-md">
        <div className="">
          {/* Logo */}
          <div className="mb-8 h-8 w-auto">
            <Logo />
          </div>

          <div className="border p-10 rounded-lg shadow bg-white">
            {/* Testimonial Content */}
            <div className="mb-6">
              <h2 className="text-xl font-semibold text-[#0A0A0A] mb-4">
                A Breath of Fresh Air
              </h2>
              <p className="text-[#737373] leading-relaxed mb-6">
                &quot;{testimonial.review}&quot;
              </p>
            </div>

            {/* Author */}
            <div className="flex items-center gap-4">
              <Avatar className="w-12 h-12">
                {testimonial.authorImage && (
                  <AvatarImage
                    src={testimonial.authorImage}
                    alt={testimonial.author}
                  />
                )}
                <AvatarFallback className="bg-[#005F5A] text-white">
                  {testimonial.author[0]}
                </AvatarFallback>
              </Avatar>
              <div>
                <div className="flex items-center gap-1 mb-1">
                  {Array.from({ length: 5 }).map((_, index) => (
                    <Star
                      key={index}
                      className={cn(
                        "w-4 h-4",
                        testimonial.rating > index
                          ? "text-yellow-400 fill-yellow-400"
                          : "text-gray-300"
                      )}
                    />
                  ))}
                </div>
                <p className="text-sm font-medium text-[#0A0A0A]">
                  {testimonial.author}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
