import { redirect } from "next/navigation";
import { cookies } from "next/headers";
import { getMeAction } from "@/actions/user";
import { AuthBootstrap } from "./_components/auth-bootstrap";

const AUTH_TOKEN_NAME = process.env.AUTH_TOKEN_NAME || 'secure_cookie_name';

export default async function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  // Check for auth token
  const token = (await cookies()).get(AUTH_TOKEN_NAME);

  if (!token) {
    redirect("/login");
  }

  // Fetch user data (now includes everything)
  const meResult = await getMeAction();

  if (!meResult.success || !meResult.data) {
    redirect("/login");
  }

  const { user, organizations, workspaces, current_workspace_permissions, current_workspace_pending_invitations, current_organization_permissions, current_organization_plan_limits } = meResult.data;

  return (
    <AuthBootstrap
      user={user}
      organizations={organizations}
      workspaces={workspaces}
      current_workspace_permissions={current_workspace_permissions}
      current_workspace_pending_invitations={current_workspace_pending_invitations}
      current_organization_permissions={current_organization_permissions}
      current_organization_plan_limits={current_organization_plan_limits}
    >
      {children}
    </AuthBootstrap>
  );
}