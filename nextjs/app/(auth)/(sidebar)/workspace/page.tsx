import { WorkspaceManagement } from "./_components/workspace-management";
import { getWorkspaceMembersAction } from "@/actions/workspace";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Workspace | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Manage your workspace and team members",
};

export default async function WorkspacePage() {
  // Fetch unified data on server to eliminate client loading states and blink
  const result = await getWorkspaceMembersAction();

  return (
    <WorkspaceManagement
      initialData={result.success ? result.data : undefined}
    />
  );
}