import { DashboardPageClient } from "./_components/dashboard-page-client";
import { getDashboardDataAction } from "@/actions/dashboard";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Dashboard | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: `View your ${process.env.NEXT_PUBLIC_APP_NAME} dashboard`,
};

export default async function DashboardPage() {
  // Fetch data on server to eliminate client loading states and blink
  const dashboardResult = await getDashboardDataAction();

  return (
    <DashboardPageClient
      initialDashboardData={dashboardResult.success ? dashboardResult.data : null}
    />
  );
}