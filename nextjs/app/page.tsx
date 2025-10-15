import { checkAuthStatus } from "@/actions/user";
import { redirect } from "next/navigation";

export default async function Home() {
  const isAuthenticated = await checkAuthStatus();
  if (!isAuthenticated) {
    redirect("/join-waitlist");
  } else {
    redirect("/dashboard");
  }
}
