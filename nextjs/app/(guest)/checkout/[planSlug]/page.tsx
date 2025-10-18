import { Metadata } from "next";
import { redirect } from "next/navigation";
import { getPlansAction } from "@/actions/plans";
import { getMeAction } from "@/actions/user";
import { CheckoutPageClient } from "./_components/checkout-page-client";

export const metadata: Metadata = {
  title: `Checkout | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Complete your purchase",
};

interface CheckoutPageProps {
  params: Promise<{ planSlug: string }>;
}

export default async function CheckoutPage({ params }: CheckoutPageProps) {
  const { planSlug } = await params;

  // Fetch plans to get the selected plan details
  const plansData = await getPlansAction();

  if (!plansData.success || !plansData.plans) {
    redirect("/pricing");
  }

  const plan = plansData.plans[planSlug];

  if (!plan) {
    redirect("/pricing");
  }

  // Don't allow checkout for free plans
  if (plan.is_free) {
    redirect("/pricing");
  }

  // Fetch user data server-side (for page refresh support)
  const userData = await getMeAction();

  return <CheckoutPageClient plan={plan} initialUserData={userData.success ? userData.data : null} />;
}
