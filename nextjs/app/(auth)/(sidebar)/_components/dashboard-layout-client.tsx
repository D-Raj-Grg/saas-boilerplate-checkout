"use client";

import { Sidebar } from "./sidebar";
import { Button } from "@/components/ui/button";
import { Bell, LogOut, User, PanelRightOpen, PanelRightClose } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { useEffect } from "react";
import { toast } from "sonner";
import { logoutAction } from "@/actions/auth";
import { useUserStore } from "@/stores/user-store";
import { loadUserData } from "@/lib/auth-client";
import { ErrorBoundary } from "./error-boundary";
import { EmailVerificationBanner } from "./email-verification-banner";
import { cn } from "@/lib/utils";
import { useSidebarStore } from "@/stores/sidebar-store";
import { EmailVerificationNotification } from "@/app/(auth)/(sidebar)/_components/email-verification-notification";
import { ReceivedInvitationsNotification } from "@/app/(auth)/(sidebar)/_components/received-invitations-notification";
import { usePendingNotifications } from "@/hooks/use-pending-notifications";

interface DashboardLayoutClientProps {
  children: React.ReactNode;
}

export function DashboardLayoutClient({ children }: DashboardLayoutClientProps) {
  const router = useRouter();
  const { userData, clearUser, getInitials } = useUserStore();
  const user = userData?.user || null;
  const { collapsed } = useSidebarStore();
  const { hasNotifications } = usePendingNotifications();

  // Simple: Load user data on mount if not already loaded
  useEffect(() => {
    if (!userData) {
      loadUserData();
    }
  }, [userData]);

  async function handleLogout() {
    await logoutAction();
    clearUser();
    toast.success("Logged out successfully", {
      description: "See you next time!",
    });
    router.push("/login");
  }

  return (
    <div className="h-screen bg-sidebar relative overflow-hidden">
      {/* Skip Link */}
      <a
        href="#main-content"
        className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-primary focus:text-primary-foreground focus:px-4 focus:py-2 focus:rounded-md focus:no-underline"
      >
        Skip to main content
      </a>

      {/* Sidebar */}
      <Sidebar />

      {/* Main Content Area - with rounded corners and margin */}
      <div className={cn(
        "absolute inset-2 bg-background transition-all duration-300 flex flex-col overflow-hidden border",
        collapsed ? "left-16" : "left-[256px]",
        "rounded-2xl"
      )}>
        {/* Top Header */}
        <header className="h-16 border-b bg-card flex items-center justify-between px-4 md:px-6">
          <div className="flex items-center gap-2 md:gap-4 min-w-0 flex-1">
            {/* Sidebar Toggle */}
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-8 text-foreground hover:bg-accent border"
              onClick={() => {
                const { toggle } = useSidebarStore.getState();
                toggle();
              }}
            >
              {collapsed ? (
                <PanelRightClose className="h-4 w-4" />
              ) : (
                <PanelRightOpen className="h-4 w-4" />
              )}
            </Button>

          </div>

          <div className="flex items-center gap-3">
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative border">
                  <Bell className="h-5 w-5" />
                  {hasNotifications && (
                    <div className="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full border-2 border-background"></div>
                  )}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-80">
                <DropdownMenuLabel>Notifications</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <EmailVerificationNotification />
                <ReceivedInvitationsNotification />
                {!hasNotifications && (
                  <div className="p-6 text-center text-sm text-muted-foreground">
                    No notifications
                  </div>
                )}
              </DropdownMenuContent>
            </DropdownMenu>

            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="rounded-full">
                  <div className="size-9 rounded-full bg-white border flex items-center justify-center text-secondary text-normal font-bold">
                    {getInitials().charAt(0)?.toUpperCase() || "A"}
                  </div>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>
                  <div className="flex flex-col space-y-1">
                    <p className="text-sm font-medium leading-none">{user?.name || "User"}</p>
                    <p className="text-xs leading-none text-muted-foreground">
                      {user?.email}
                    </p>
                  </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                  <Link href="/profile" className="hover:cursor-pointer">
                    <User className="mr-2 h-4 w-4" />
                    My Profile
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={handleLogout} className="hover:cursor-pointer">
                  <LogOut className="mr-2 h-4 w-4" />
                  Logout
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </header>

        {/* Email Verification Banner */}
        {user && !user.email_verified_at && (
          <div className="py-2 bg-yellow-50">
            <EmailVerificationBanner
              email={user.email}
            />
          </div>
        )}

        {/* Page Content */}
        <main id="main-content" className="flex-1 overflow-y-auto overflow-x-hidden">
          <div className="px-4 py-6 lg:px-6 lg:py-8">
            <ErrorBoundary>
              {children}
            </ErrorBoundary>
          </div>
        </main>
      </div>
    </div>
  );
}