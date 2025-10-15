import { getOrganizationMembersAction } from "@/actions/organization";
import { OrganizationMembersPage } from "./_components/organization-members-page";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Organization Members | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Manage your organization's members and invitations",
};

export default async function MembersPage() {
  const membersResult = await getOrganizationMembersAction();
  const membersData = membersResult.success ? membersResult.data : null;

  return (
    <OrganizationMembersPage
      initialMembers={membersData?.current_members || []}
      initialPendingInvitations={membersData?.pending_invitations || []}
    />
  );
}