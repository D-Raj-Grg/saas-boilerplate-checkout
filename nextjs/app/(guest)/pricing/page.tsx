import { Suspense } from "react";
import { PricingPageClient } from "./_components/pricing-page-client";
import { Metadata } from "next";
import { getPlansAction } from "@/actions/plans";

export const metadata: Metadata = {
    title: `Pricing | ${process.env.NEXT_PUBLIC_APP_NAME}`,
    description: "Simple, flexible, and affordable pricing plans for A/B testing and optimization.",
};

export default async function PricingPage() {
    const plansData = await getPlansAction();

    return (
        <Suspense fallback={
            <div className="min-h-screen bg-background flex items-center justify-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>
        }>
            <PricingPageClient plansData={plansData} />
        </Suspense>
    );
}
