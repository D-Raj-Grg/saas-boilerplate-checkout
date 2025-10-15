"use client";

import { useRouter } from "next/navigation";
import { useUserStore } from "@/stores/user-store";
import { UserOrganization, UserWorkspace } from "@/interfaces/user";
import { Plus, Users, Star } from "lucide-react";
import { Button } from "@/components/ui/button";
import { RequiresPermission } from "@/components/requires-permission";
import { PlanGatedFeature } from "@/components/plan-gated-feature";
import { setCurrentWorkspaceAction } from "@/actions/user-preferences";
import { toast } from "sonner";
import { useState, useMemo } from "react";
import { loadUserData } from "@/lib/auth-client";
import { logoutAction } from "@/actions/auth";
import { cn } from "@/lib/utils";
import { CreateWorkspaceDialog } from "@/app/(auth)/(sidebar)/_components/create-workspace-dialog";
import { Logo } from "@/components/ui/logo";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import Link from "next/link";

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

interface WorkspacesListProps {
  organization: UserOrganization;
}

export function WorkspacesList({ organization }: WorkspacesListProps) {
  const router = useRouter();
  const { userData, selectedWorkspace, setSelectedWorkspace, clearUser } = useUserStore();
  const [createWorkspaceOpen, setCreateWorkspaceOpen] = useState(false);
  const [isLoading, setIsLoading] = useState<string | null>(null);

  // Random testimonial selection that changes on page refresh
  const randomTestimonial = useMemo(() => {
    return testimonials[Math.floor(Math.random() * testimonials.length)];
  }, []);

  // Get workspaces for this organization
  const workspaces = userData?.workspaces?.filter(
    w => w.organization_uuid === organization.uuid
  ) || [];

  const handleSwitchOrganization = () => {
    router.push('/organizations');
  };

  const handleWorkspaceCreated = async () => {
    // Refresh user data to show the new workspace
    await loadUserData();
  };

  const handleLogout = async () => {
    await logoutAction();
    clearUser();
    toast.success("Logged out successfully", {
      description: "See you next time!",
    });
    router.push("/login");
  };

  const handleWorkspaceSelect = async (workspace: UserWorkspace) => {
    setIsLoading(workspace.uuid);

    try {
      const result = await setCurrentWorkspaceAction(workspace.uuid);

      if (result.success) {
        setSelectedWorkspace(workspace);
        toast.success("Workspace switched successfully");
        router.push('/dashboard');
      } else {
        toast.error("Failed to switch workspace: " + result.error);
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setIsLoading(null);
    }
  };



  if (workspaces.length === 0) {
    return (
      <div className="min-h-screen grid grid-cols-1 lg:grid-cols-[45%_55%]">
        {/* Left Panel - Testimonial */}
        <TestimonialPanel testimonial={randomTestimonial} />

        {/* Right Panel - No Workspaces */}
        <div className="bg-white sticky top-0 h-screen overflow-y-auto">
          <div className="flex pt-20 min-h-full p-10 justify-start">
            <div className="max-w-xl w-full">
              {/* Header */}
              <div className="mb-8">
                <div className="text-left">
                  <h1 className="text-2xl font-semibold text-[#0A0A0A] mb-2">
                    Your Workspaces
                  </h1>
                  <p className="text-[#737373] mb-4">
                    Select which workspace you want to continue with, or create a new one.
                  </p>
                  <div className="text-sm text-[#737373]">
                    You&apos;re in organization <span className="font-medium text-[#0A0A0A]">{organization.name}</span>
                  </div>
                </div>
              </div>

              {/* Empty State */}
              <div className="text-center py-12">
                <Users className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-xl font-medium text-foreground mb-2">No Workspaces</h3>
                <p className="text-muted-foreground mb-6">
                  This organization doesn&apos;t have any workspaces yet. Create your first workspace to get started.
                </p>

                <RequiresPermission orgPermission="can_create_workspaces">
                  <PlanGatedFeature feature="workspaces" className="w-full">
                    <Button onClick={() => setCreateWorkspaceOpen(true)} size="lg" className="w-full">
                      <Plus className="h-4 w-4 mr-2" />
                      Create First Workspace
                    </Button>
                  </PlanGatedFeature>
                </RequiresPermission>
              </div>

              {/* Footer */}
              <div className="pt-6 text-left text-sm text-[#737373]">
                <p>
                  You&apos;re in organization <span className="font-medium text-[#0A0A0A]">{organization.name}</span>.{" "}
                  <button
                    onClick={handleSwitchOrganization}
                    className="text-[#005F5A] hover:underline"
                  >
                    Switch Organization
                  </button>
                </p>
                <div className="mt-2">
                  <span>You&apos;re logged in as <span className="font-medium text-[#0A0A0A]">{userData?.user?.email}</span></span>. <button
                    onClick={handleLogout}
                    className="text-[#005F5A] hover:underline"
                  >
                    Logout
                  </button>
                </div>
                <div className="mt-1">
                  <span>Need to manage or delete your user account? <button className="text-[#005F5A] hover:underline">Edit Profile</button></span>
                </div>
              </div>

              {/* Create Workspace Dialog */}
              <CreateWorkspaceDialog
                open={createWorkspaceOpen}
                onOpenChange={setCreateWorkspaceOpen}
                targetOrganization={organization}
                onWorkspaceCreated={handleWorkspaceCreated}
              />
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen grid grid-cols-1 lg:grid-cols-[45%_55%]">
      {/* Left Panel - Testimonial */}
      <TestimonialPanel testimonial={randomTestimonial} />

      {/* Right Panel - Workspaces List */}
      <div className="bg-white sticky top-0 h-screen overflow-y-auto">
        <div className="flex pt-20 min-h-full p-10 justify-start">
          <div className="max-w-xl w-full">
            {/* Header */}
            <div className="mb-8">
              <div className="text-left">
                <h1 className="text-2xl font-semibold text-[#0A0A0A] mb-2">
                  Your Workspaces
                </h1>
                <p className="text-[#737373] mb-4">
                  Select which workspace you want to continue with, or create a new one.
                </p>
              </div>
            </div>

            {/* Workspaces List - No spacing between items */}
            <div className="mb-6 border border-[#E5E5E5] rounded-lg overflow-hidden">
              {workspaces.map((workspace, index) => {
                const isCurrentWorkspace = selectedWorkspace?.uuid === workspace.uuid;
                const isWorkspaceLoading = isLoading === workspace.uuid;

                return (
                  <button
                    key={workspace.uuid}
                    onClick={() => handleWorkspaceSelect(workspace)}
                    disabled={isWorkspaceLoading}
                    className={cn(
                      "w-full p-4 bg-white text-left transition-all duration-200 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed",
                      index !== workspaces.length - 1 && "border-b border-[#E5E5E5]",
                      isCurrentWorkspace && "bg-primary/5"
                    )}
                  >
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-4 flex-1">
                        <div className={cn(
                          "h-10 w-10 rounded-full border flex items-center justify-center text-sm font-medium",
                          isCurrentWorkspace
                            ? "border-primary text-primary bg-primary/10"
                            : "border-[#E5E5E5] text-muted-foreground"
                        )}>
                          {workspace.name.charAt(0).toUpperCase()}
                        </div>

                        <div className="flex items-center gap-2 flex-1">
                          <h3 className="text-base font-medium text-[#0A0A0A]">
                            {workspace.name}
                          </h3>
                          {isCurrentWorkspace && (
                            <div className="px-2 py-0.5 bg-primary/10 text-primary text-xs font-medium rounded-full">
                              Current
                            </div>
                          )}
                        </div>
                      </div>

                      {isWorkspaceLoading && (
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

            {/* Create New Workspace Button */}
            <RequiresPermission orgPermission="can_create_workspaces">
              <PlanGatedFeature feature="workspaces" className="w-full">
                <Button
                  onClick={() => setCreateWorkspaceOpen(true)}
                  className="w-full h-12 text-base font-medium"
                  size="lg"
                >
                  <Plus className="h-4 w-4 mr-2" />
                  New Workspace
                </Button>
              </PlanGatedFeature>
            </RequiresPermission>

            {/* Footer */}
            <div className="pt-6 text-left text-sm text-[#737373]">
              <p>
                You&apos;re in organization <span className="font-medium text-[#0A0A0A]">{organization.name}</span>.{" "}
                <button
                  onClick={handleSwitchOrganization}
                  className="text-[#005F5A] hover:underline"
                >
                  Switch Organization
                </button>
              </p>
              <div className="mt-2">
                <span>You&apos;re logged in as <span className="font-medium text-[#0A0A0A]">{userData?.user?.email}</span></span>. <button
                  onClick={handleLogout}
                  className="text-[#005F5A] hover:underline"
                >
                  Logout
                </button>
              </div>
              <div className="mt-1">
                <span>Need to manage or delete your user account? <button className="text-[#005F5A] hover:underline"> <Link href='/profile'>
                  Edit Profile
                </Link></button></span>
              </div>
            </div>

            {/* Create Workspace Dialog */}
            <CreateWorkspaceDialog
              open={createWorkspaceOpen}
              onOpenChange={setCreateWorkspaceOpen}
              targetOrganization={organization}
              onWorkspaceCreated={handleWorkspaceCreated}
            />
          </div>
        </div>
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