import { OrganizationOverview } from "./_components/organization-overview";
import { getOrganizationStatsAction } from "@/actions/organization";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `General Settings | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Configure your organization's general settings",
};

export default async function OrganizationPage() {
  const statsResult = await getOrganizationStatsAction();

  return (
    <OrganizationOverview
      organizationStats={statsResult.success ? statsResult.data : null}
    />
  );
}