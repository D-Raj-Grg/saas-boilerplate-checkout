import { OrganizationsSwitcher } from "./_components/organizations-switcher";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Switch Organizations | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Switch between your organizations and workspaces",
};

interface OrganizationsPageProps {
  searchParams: Promise<{
    org_id?: string;
  }>;
}

export default async function OrganizationsPage({ searchParams }: OrganizationsPageProps) {
  const params = await searchParams;
  return (
    <OrganizationsSwitcher orgId={params.org_id} />
  );
}