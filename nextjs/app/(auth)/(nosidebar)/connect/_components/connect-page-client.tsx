"use client";

import { useState, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue, SelectGroup, SelectLabel } from "@/components/ui/select";
import { initiateConnectionAction } from "@/actions/connection";
import { logoutAction } from "@/actions/auth";
import { useUserStore } from "@/stores/user-store";
import { setRedirectUrl } from "@/lib/redirect-cookie";
import { setCurrentWorkspaceAction } from "@/actions/user-preferences";
import { UserOrganization, UserWorkspace } from "@/interfaces/user";
import Image from "next/image";
import GravatarAvatar from "./GravatarAvatar";
import Link from "next/link";


export function ConnectPageClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { userData, selectedWorkspace, setSelectedWorkspace } = useUserStore();
  const [isLoading, setIsLoading] = useState(false);
  const [isSwitchingWorkspace, setIsSwitchingWorkspace] = useState(false);
  const redirectUrl = searchParams.get("oauth_url");
  const userEmail = userData?.user?.email || "your account";

  useEffect(() => {
    // If no redirect URL is provided, show an error
    if (!redirectUrl) {
      toast.error("Missing redirect URL", {
        description: "Please provide a valid site URL to connect.",
      });
    }
  }, [redirectUrl]);

  // Set the selected workspace based on current context
  useEffect(() => {
    if (userData?.user?.current_workspace_uuid && userData.workspaces) {
      const currentWorkspace = userData.workspaces.find(
        w => w.uuid === userData.user.current_workspace_uuid
      );
      if (currentWorkspace && (!selectedWorkspace || selectedWorkspace.uuid !== currentWorkspace.uuid)) {
        setSelectedWorkspace(currentWorkspace);
      }
    }
  }, [userData, selectedWorkspace, setSelectedWorkspace]);

  async function handleConnect() {
    if (!redirectUrl) {
      toast.error("Missing Redirect URL");
      return;
    }

    try {
      setIsLoading(true);

      const result = await initiateConnectionAction(redirectUrl);

      if (result.success && result.data) {
        toast.success("Connection initiated!", {
          description: "Redirecting to complete the connection...",
        });

        // Redirect to the external site with the temporary token
        window.location.href = result.data.redirect_url;
      } else {
        toast.error("Connection failed", {
          description: result.error || "Unable to initiate connection. Please try again.",
        });
      }
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred. Please try again.",
      });
    } finally {
      setIsLoading(false);
    }
  }

  async function handleLogout() {
    try {
      // Set redirect URL in cookie for after login
      setRedirectUrl("/connect?oauth_url=" + encodeURIComponent(redirectUrl || ''));
      await logoutAction();
      router.push("/login?redirect_url=/connect?oauth_url=" + encodeURIComponent(redirectUrl || ""));
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred during logout.",
      });
    }
  }

  async function handleWorkspaceChange(workspaceUuid: string) {
    const workspace = userData?.workspaces?.find(w => w.uuid === workspaceUuid);
    if (!workspace) return;

    try {
      setIsSwitchingWorkspace(true);
      setSelectedWorkspace(workspace);

      const result = await setCurrentWorkspaceAction(workspace.uuid);

      if (!result.success) {
        toast.error("Failed to switch workspace", {
          description: result.error || "Unable to switch workspace. Please try again.",
        });
      } else {
        toast.success("Workspace switched successfully");

        // Reload user data to get updated current_context
        const { loadUserData } = await import("@/lib/auth-client");
        await loadUserData();

        // Refresh the router to update server components
        router.refresh();
      }
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred while switching workspace.",
      });
    } finally {
      setIsSwitchingWorkspace(false);
    }
  }

  // Group workspaces by organization
  const workspacesByOrg = userData?.organizations?.reduce((acc, org) => {
    const orgWorkspaces = userData.workspaces?.filter(
      w => w.organization_uuid === org.uuid
    ) || [];
    if (orgWorkspaces.length > 0) {
      acc.push({ organization: org, workspaces: orgWorkspaces });
    }
    return acc;
  }, [] as Array<{ organization: UserOrganization; workspaces: UserWorkspace[] }>) || [];



  return (
    <div className="flex flex-col justify-center items-center w-full h-full pt-10">
      <div className="w-[550px] max-w-[800px] mx-auto bg-white dark:bg-background shadow-sm rounded-md p-10 text-center">
        <h2 className="text-2xl text-app-heading dark:text-foreground font-semibold mb-3">
          One Last Step
        </h2>
        <p className="text-sm text-app-text mb-10 font-normal">
          Let&apos;s connect your {process.env.NEXT_PUBLIC_APP_NAME} account with this website.
        </p>

        <div className="grid grid-cols-3 items-center mb-[30px]">
          <div className="justify-self-end">
            <Image
              src="/logo.svg"
              alt={`${process.env.NEXT_PUBLIC_APP_NAME} Logo`}
              className="w-12 h-12 object-contain shrink-0 mx-auto"
              width="40"
              height="40"
            />
          </div>
          <div className="relative">
            {userEmail && (
              <GravatarAvatar
                email={userEmail}
                displayName={userEmail}
                size={8}
                className="border border-grey-800 rounded-full self-center inline-block text-center absolute -bottom-4 -ml-4 bg-white dark:bg-background"
              />
            )}
            <hr className="border-dashed border" />
          </div>
          <div className="justify-self-start">
            <Image
              src="/images/wordpress-core-icon.png"
              alt="Logo"
              className="w-12 h-12 object-contain shrink-0 mx-auto"
              width="40"
              height="40"
            />

          </div>
        </div>

        <p className="text-sm text-app-text mt-5">

          Allow us to connect to your{" "}
          <span className="text-black font-semibold">
            {userEmail}
          </span>
          <br />
          account for {process.env.NEXT_PUBLIC_APP_NAME} in the workspace below.
        </p>

        <div className="my-4 text-center">
          <div className="flex justify-center items-center w-full relative">
            <Select
              value={selectedWorkspace?.uuid || ""}
              onValueChange={handleWorkspaceChange}
              disabled={isSwitchingWorkspace}
            >
              <SelectTrigger className="w-60 hover:ring-1 ring-primary">
                <SelectValue placeholder="Select a workspace">
                  {selectedWorkspace?.name || "Select a workspace"}
                </SelectValue>
              </SelectTrigger>
              <SelectContent className="w-60 max-h-60">
                {workspacesByOrg.map(({ organization, workspaces }) => (
                  <SelectGroup key={organization.uuid}>
                    <SelectLabel className="text-xs font-semibold text-gray-500 truncate">
                      {organization.name}
                    </SelectLabel>
                    {workspaces.map((workspace) => (
                      <SelectItem
                        key={workspace.uuid}
                        value={workspace.uuid}
                        className="pl-6 [&>span]:truncate truncate cursor-pointer"
                      >
                        {workspace.name}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        <div className="flex items-center mb-5 text-center justify-center w-full">
          <Button
            onClick={handleConnect}
            disabled={isLoading || !redirectUrl || isSwitchingWorkspace || !selectedWorkspace}
            className="button w-60"

          >
            Authorize & Connect
          </Button>
        </div>
        <div className="text-app-text font-normal text-sm">
          Not You?{" "}
          <Button
            variant="link"
            onClick={handleLogout}
            className="text-app-text underline p-0 h-auto font-normal"
          >
            Use a different account.
          </Button>
        </div>

        <p className="text-sm text-app-text mt-5">
          By clicking, you agree to our{" "}
          <Link
            href="/terms-and-conditions/"
            className="text-primary font-medium"
            target="_blank"
          >
            Terms
          </Link>{" "}
          and{" "}
          <Link
            href="/privacy-policy/"
            className="text-primary font-medium"
            target="_blank"
          >
            Privacy Policy
          </Link>
        </p>
      </div>
    </div>
  );
}