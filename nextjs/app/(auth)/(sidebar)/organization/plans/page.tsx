import { getOrganizationStatsAction } from "@/actions/organization";
import { PlansManagement } from "./_components/plans-management";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Plans & Subscriptions | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Manage your organization's plan and subscription",
};

export default async function PlansPage() {
  const statsResult = await getOrganizationStatsAction();

  return (
    <PlansManagement
      organizationStats={statsResult.success ? statsResult.data : null}
    />
  );
}