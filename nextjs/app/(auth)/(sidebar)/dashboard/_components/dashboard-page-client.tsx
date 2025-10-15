"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { getDashboardDataAction } from "@/actions/dashboard";
import { toast } from "sonner";

interface DashboardData {
  title: string;
  user: {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    email: string;
    current_organization: {
      name: string;
    };
    current_workspace: {
      name: string;
    };
  };
}

interface DashboardPageClientProps {
  initialDashboardData?: DashboardData | null;
}

export function DashboardPageClient({
  initialDashboardData = null,
}: DashboardPageClientProps) {
  const router = useRouter();
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(initialDashboardData);
  const [dashboardLoading, setDashboardLoading] = useState(!initialDashboardData);

  // Check for error parameters in URL and show toast
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');

    if (error === 'insufficient_permissions') {
      toast.error('Access Denied', {
        description: 'You do not have permission to access the Organizations page.'
      });
      // Clean up URL
      router.replace('/dashboard');
    }
  }, [router]);

  // Load dashboard data
  useEffect(() => {
    if (!initialDashboardData) {
      async function loadDashboardStats() {
        try {
          const dashboardResult = await getDashboardDataAction();
          if (dashboardResult.success && dashboardResult.data) {
            setDashboardData(dashboardResult.data);
          } else {
            toast.error('Failed to load dashboard data');
          }
        } catch {
          toast.error('An error occurred while loading dashboard data');
        } finally {
          setDashboardLoading(false);
        }
      }
      loadDashboardStats();
    }
  }, [initialDashboardData]);

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">
            Welcome back, {dashboardData?.user.first_name || 'User'}! 👋🏼
          </h1>
          {dashboardLoading ? (
            <div className="h-4 w-96 bg-muted rounded animate-pulse mt-1" />
          ) : (
            <p className="text-muted-foreground mt-1">
              Here&apos;s an overview of your account.
            </p>
          )}
        </div>
      </div>

      {/* Dashboard Content Placeholder */}
      <Card>
        <CardContent className="p-6">
          <div className="text-center py-12">
            <h3 className="text-xl font-semibold mb-2">Welcome to {process.env.NEXT_PUBLIC_APP_NAME}</h3>
            <p className="text-muted-foreground">
              Your dashboard is ready. Add your custom content here.
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
