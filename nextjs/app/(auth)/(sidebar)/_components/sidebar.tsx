"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import { cn } from "@/lib/utils";
import {
  LayoutDashboard,
  Settings,
  // HelpCircle,
  Users,
  Workflow,
  ArrowUpRightIcon,
  CreditCard,
  Info,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Logo } from "@/components/ui/logo";
import { useSidebarStore } from "@/stores/sidebar-store";
// import { usePermissions } from "@/hooks/use-permissions";
import { usePlanLimits } from "@/stores/user-store";
import { UsageStatsDialog } from "./usage-stats-dialog";
import { OrganizationWorkspaceSwitcher } from "./organization-workspace-switcher";
import { Separator } from "@/components/ui/separator";
import { Progress } from "@/components/ui/progress";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";

const navigation = [
  { name: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  { name: "Connections", href: "/connections", icon: Workflow }
];

const baseBottomNavigation = [
  { name: "Members", href: "/workspace", icon: Users },
  { name: "Settings", href: "/settings", icon: Settings }
];

const organizationNavigation = [
  {
    name: "General",
    href: "/organization",
    icon: Settings,
  },
  { name: "Members", href: "/organization/members", icon: Users },
  {
    name: "Plans & Billing",
    href: "/organization/plans",
    icon: CreditCard,
  },

];

export function Sidebar() {
  const pathname = usePathname();
  const { collapsed } = useSidebarStore();
  const planLimits = usePlanLimits();
  const [usageStatsOpen, setUsageStatsOpen] = useState(false);
  // Check if we're on organization routes
  const isOrganizationRoute = pathname.startsWith("/organization");

  // Use base bottom navigation without organizations
  const bottomNavigation = baseBottomNavigation;

  return (
    <aside
      className={cn(
        "fixed left-0 top-0 h-screen flex flex-col bg-sidebar text-sidebar-foreground transition-all duration-300 overflow-hidden z-40 overflow-y-auto",
        collapsed ? "w-16" : "w-64"
      )}
      role="navigation"
      aria-label="Main navigation"
    >
      {/* Content */}
      <div className="flex h-full flex-1 flex-col justify-between">
        <div className="relative z-10 flex flex-col">
          {/* Logo Section */}
          <div
            className={`flex items-center px-4 py-6 transition-all duration-300 ease-in-out ${collapsed ? "justify-center" : "px-6 justify-start"
              }`}
          >
            <Link href="/dashboard" className="h-8 w-auto">
              <Logo collapsed={collapsed} />
            </Link>
          </div>
          <span className="hidden bg-white text-primary"></span>

          <div
            className={cn("space-y-2", collapsed ? "px-3 " : "px-4")}
            role="list"
          >
            <OrganizationWorkspaceSwitcher collapsed={collapsed} />
          </div>
          <div className={cn("py-4", collapsed ? "px-3 " : "px-4")}>
            <Separator className="w-full bg-border h-1" />
          </div>

          {/* Main Navigation - Hidden on organization routes */}
          {!isOrganizationRoute && (
            <nav
              className={cn("space-y-2", collapsed ? " px-3" : "px-4")}
              role="list"
            >
              {navigation.map((item) => {
                const isActive = pathname.includes(item.href);
                return (
                  <Link
                    key={item.name}
                    href={item.href}
                    className={cn(
                      "flex items-center rounded-md text-sm font-medium ",
                      collapsed ? "justify-center p-2.5" : "gap-3 px-3 py-2",
                      isActive
                        ? "bg-white text-primary outline"
                        : "text-muted-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground"
                    )}
                    aria-current={isActive ? "page" : undefined}
                    title={collapsed ? item.name : undefined}
                  >
                    <item.icon
                      className="h-5 w-5 flex-shrink-0"
                      aria-hidden="true"
                    />
                    {!collapsed && <span>{item.name}</span>}
                  </Link>
                );
              })}
            </nav>
          )}

          {/* Organization Navigation - Shown only on organization routes */}
          {isOrganizationRoute && (
            <nav
              className={cn("space-y-2", collapsed ? " px-3" : "px-4")}
              role="list"
            >
              {organizationNavigation.map((item) => {
                const isActive = pathname === item.href;
                return (
                  <Link
                    key={item.name}
                    href={item.href}
                    className={cn(
                      "flex items-center rounded-md text-sm font-medium",
                      collapsed ? "justify-center p-2.5" : "gap-3 px-3 py-2",
                      isActive
                        ? "bg-white text-primary outline"
                        : "text-muted-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground"
                    )}
                    aria-current={isActive ? "page" : undefined}
                    title={collapsed ? item.name : undefined}
                  >
                    <item.icon
                      className="h-5 w-5 flex-shrink-0"
                      aria-hidden="true"
                    />
                    {!collapsed && <span>{item.name}</span>}
                  </Link>
                );
              })}
            </nav>
          )}

          {!isOrganizationRoute && <div className={cn("py-3", collapsed ? "px-3 " : "px-4")}>
            <Separator className="w-full bg-border h-1" />
          </div>}

          {/* Bottom Navigation - Hidden on organization routes */}
          {!isOrganizationRoute && (
            <div
              className={cn("space-y-2", collapsed ? " px-3" : "px-4")}
              role="list"
            >
              {bottomNavigation.map((item) => {
                const isActive = pathname === item.href;
                return (
                  <Link
                    key={item.name}
                    href={item.href}
                    className={cn(
                      "flex items-center rounded-md text-sm font-medium",
                      collapsed ? "justify-center p-2.5" : "gap-3 px-3 py-2",
                      isActive
                        ? "bg-white text-primary outline"
                        : "text-muted-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground"
                    )}
                    aria-current={isActive ? "page" : undefined}
                    title={collapsed ? item.name : undefined}
                  >
                    <item.icon
                      className="h-5 w-5 flex-shrink-0"
                      aria-hidden="true"
                    />
                    {!collapsed && <span>{item.name}</span>}
                  </Link>
                );
              })}
            </div>
          )}
        </div>
        <div>
          {/* Usage Stats Section - Hidden on organization routes */}
          {!collapsed && !isOrganizationRoute && (
            <div className="p-4">
              <div className="bg-white border border-border rounded-md py-3 px-4 shadow-md">
                <div className="flex items-center justify-between mb-2">
                  <h3 className="font-semibold text-base">
                    {planLimits?.plan?.name || "Loading..."} Plan
                  </h3>
                  {planLimits?.trial?.is_trial && (
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <Info className="h-4 w-4 text-muted-foreground cursor-help" />
                        </TooltipTrigger>
                        <TooltipContent className="max-w-xs">
                          <div className="space-y-1">
                            <p className="font-semibold">
                              {planLimits.trial.is_expired
                                ? "Trial Expired"
                                : "Trial Active"}
                            </p>
                            <p className="text-xs">
                              {planLimits.trial.is_expired
                                ? `Trial ended ${Math.abs(planLimits.trial.days_remaining)} days ago`
                                : `${planLimits.trial.days_remaining} days remaining`}
                            </p>
                            <p className="text-xs text-muted-foreground">
                              Ends: {new Date(planLimits.trial.ends_at).toLocaleDateString()}
                            </p>
                          </div>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  )}
                </div>
                <Separator className="w-full bg-border h-1 mb-3" />

                {planLimits?.features && Object.keys(planLimits.features).length > 0 ? (
                  <div className="space-y-5">

                    <div className="space-y-2">
                      <div className="flex items-center justify-between text-xs text-muted-foreground">
                        <span>Unique Visitors</span>
                        <span className="font-semibold">
                          {planLimits?.features?.feature
                            ? `${planLimits.features.feature.current} / ${planLimits.features.feature.limit === -1
                              ? "Unlimited"
                              : planLimits.features.feature.limit
                            }`
                            : "0 / 0"}
                        </span>
                      </div>
                      <div className="w-full bg-background rounded-full h-1">
                        <Progress
                          value={
                            planLimits?.features?.feature?.percentage || 0
                          }
                        />
                      </div>
                    </div>

                    <div className="space-y-2">
                      <div className="flex items-center justify-between text-xs text-muted-foreground">
                        <span>Feature</span>
                        <span className="font-semibold">
                          {planLimits?.features?.feature
                            ? `${planLimits.features.feature.current} / ${planLimits.features.feature.limit === -1
                              ? "Unlimited"
                              : planLimits.features.feature.limit
                            }`
                            : "0 / 0"}
                        </span>
                      </div>
                      <div className="w-full bg-background rounded-full h-1">
                        <Progress
                          value={
                            planLimits?.features?.feature?.percentage || 0
                          }
                        />
                      </div>
                    </div>

                  </div>
                ) : (
                  <div className="text-center py-4">
                    <p className="text-xs text-muted-foreground">
                      {planLimits?.plan?.status !== "active"
                        ? "Plan inactive. Upgrade to continue."
                        : "No usage data available."}
                    </p>
                  </div>
                )}

                <Button
                  variant="ghost"
                  className="w-full mt-3 text-sm font-normal text-muted-foreground justify-center gap-1 items-center p-0 h-auto hover:text-foreground !bg-transparent transition-colors"
                  onClick={() => setUsageStatsOpen(true)}
                >
                  See All Usage{" "}
                  <span>
                    <ArrowUpRightIcon className="h-4 w-4" />
                  </span>
                </Button>
              </div>
            </div>
          )}

          {/* Back to Dashboard Button - Shown at bottom for organization routes */}

        </div>
      </div>

      {/* UsageStatsDialog - Hidden on organization routes */}
      {!isOrganizationRoute && (
        <UsageStatsDialog
          open={usageStatsOpen}
          onOpenChange={setUsageStatsOpen}
        />
      )}
    </aside>
  );
}
