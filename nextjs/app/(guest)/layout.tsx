import { cookies } from "next/headers";

const AUTH_TOKEN_NAME = process.env.AUTH_TOKEN_NAME || 'secure_cookie_name';

export default async function GuestLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  // Check if user is already authenticated
  const token = (await cookies()).get(AUTH_TOKEN_NAME);

  if (token) {
    // User is authenticated, redirect to dashboard
    //redirect("/dashboard");
  }

  return (
    <div className="min-h-screen bg-background h-full overflow-auto">
      {children}
    </div>
  );
}