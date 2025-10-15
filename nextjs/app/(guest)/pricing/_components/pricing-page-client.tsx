"use client";

import { useEffect, useRef, useState } from "react";
import { useSearchParams } from "next/navigation";
import { PricingHeader } from "./pricing-header";
import { PricingSlab } from "./pricing-slab";
import { CallToActionSection } from "./call-to-action-section";
import { getCTAText } from "./pricing-utils";
import { ComparisonTable } from "./comparison-table";
import { FAQSection } from "./faq-section";
import { MoneyBackGuarantee } from "./money-back-guarantee";
import { TestimonialsMarquee } from "./testimonials-marquee";
import { cn } from "@/lib/utils";
import { FlickeringGrid } from "@/components/ui/flickering-grid";
import { getCheckoutUrlAction, PlansActionResult } from "@/actions/plans";
import { toast } from "sonner";
import { useUserStore } from "@/stores/user-store";
import { loadUserData } from "@/lib/auth-client";
import { Highlighter } from "@/components/ui/highlighter";

interface PricingPageClientProps {
  plansData: PlansActionResult;
}

export function PricingPageClient({ plansData }: PricingPageClientProps) {
  const searchParams = useSearchParams();
  const featuresFoldRef = useRef<HTMLDivElement>(null);

  // Get user and organization from Zustand store
  const selectedOrganization = useUserStore(
    (state) => state.selectedOrganization
  );

  // Get current organization plan slug
  const currentOrgPlanSlug = selectedOrganization?.current_plan?.slug || null;

  const [checkingAuth, setCheckingAuth] = useState(true);
  const [loadingCheckoutFor, setLoadingCheckoutFor] = useState<string | null>(
    null
  );

  useEffect(() => {
    // Load user data if not already loaded
    const initializeAuth = async () => {
      await loadUserData();
      setCheckingAuth(false);
    };

    initializeAuth();
  }, []);

  // Reset loading state when user navigates back to page
  useEffect(() => {
    const handleVisibilityChange = () => {
      if (document.visibilityState === 'visible') {
        setLoadingCheckoutFor(null);
      }
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);

    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, []);

  // Handle checkout
  const handleCheckout = async ({
    slug,
    isActivePlan,
  }: {
    slug: string;
    isActivePlan: boolean;
  }) => {
    if (isActivePlan) return;

    setLoadingCheckoutFor(slug);

    try {
      // Get optional params from URL search params
      const source = searchParams?.get("source") || "pricing";
      const coupon = searchParams?.get("coupon") || "";
      const aff = searchParams?.get("aff") || "";

      // Get the plan directly by slug (slug already includes period like "business-yearly")
      const plan = plansData?.plans?.[slug];

      const checkout_url =
        plan?.upgrade_downgrade_url ?? plan?.checkout_url ?? undefined;

      // Call server action to get checkout URL
      const result = await getCheckoutUrlAction({
        plan_slug: slug,
        source,
        coupon,
        aff,
        checkout_url,
      });

      if (!result.success || !result.checkoutUrl) {
        toast.error(
          result.error || "Unable to create checkout link. Please try again."
        );
        setLoadingCheckoutFor(null);
        return;
      }

      // Redirect to checkout URL - keep buttons disabled until redirect completes
      window.location.href = result.checkoutUrl;
    } catch {
      toast.error("An unexpected error occurred. Please try again.");
      setLoadingCheckoutFor(null);
    }
  };

  const scrollToFeatures = () => {
    featuresFoldRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  // Visitor count mapping for each plan
  const visitorCounts: Record<string, string> = {
    "starter-yearly": "50,000 Yearly Visitors",
    "pro-yearly": "400,000 Yearly Visitors",
    "business-yearly": "1,000,000 Yearly Visitors",
  };

  // Build slabs array dynamically from API data
  const slabs = Object.values(plansData?.plans || {})
    // Filter out early-bird-lifetime and free plans from main display
    .filter(
      (plan) => plan.slug !== "early-bird-lifetime" && plan.slug !== "free"
    )
    // Sort by priority (higher priority first)
    .sort((a, b) => a.priority - b.priority)
    .map((plan, index) => {
      // Determine size variant based on index
      const sizeVariants = ["small", "medium", "large"] as const;
      const sizeVariant = sizeVariants[index % 3] || "medium";

      // Extract plan base slug (without -yearly/-monthly suffix)
      const planBaseSlug = plan.slug.replace(/-yearly|-monthly|-lifetime/, "");

      return {
        name: plan.name,
        slug: plan.slug, // Keep full slug with period
        description: plan.description,
        cost: plan.price,
        costMonthly: plan.billing_cycle === "monthly" ? plan.price : undefined,
        costAnnually: plan.billing_cycle === "yearly" ? plan.price : undefined,
        oldPrice: {
          yearly:
            plan.max_price > 0 && plan.max_price !== plan.price
              ? plan.max_price.toString()
              : undefined,
        },
        ctaText: getCTAText(planBaseSlug, currentOrgPlanSlug || undefined),
        features: {
          "What's included:": [
            visitorCounts[plan.slug] ||
            `${plan.price === 0 ? "Free" : "$" + plan.price}`,
          ],
        },
        sizeVariant,
      };
    });

  useEffect(() => {
    // Detect page hash and scroll to section
    if (window.location.hash === "#features") {
      scrollToFeatures();
    }
  }, []);

  return (
    <div
      className={cn("w-full overflow-x-hidden xl:overflow-x-visible relative")}
    >
      {/* Animated Header Background */}
      <div className="w-full h-[1120px] absolute overflow-hidden top-0 left-0 bg-gradient-to-b from-teal-50 via-white to-transparent">
        <FlickeringGrid
          className="z-0"
          squareSize={4}
          gridGap={6}
          color="rgb(0, 95, 90)"
          maxOpacity={0.1}
          flickerChance={0.2}
        />
        {/* Gradient overlay for smooth transition */}
        <div className="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-white" />
      </div>
      <div className="w-full pt-10 flex justify-center relative px-4 md:px-6 lg:px-8">
        <div className="w-full max-w-6xl xl:max-w-7xl pb-[48px] relative z-10">
          <PricingHeader dark={false} checkingAuth={checkingAuth} />
          {/* Custom Headline - Left Aligned */}
          <div className="mb-12">
            {/* Main Headline */}
            <h1 className="text-[5rem] font-bold leading-[5.5rem] mb-6 max-md:text-[3rem] max-md:leading-[3.5rem] max-sm:text-[2rem] max-sm:leading-[2.5rem]">
              Pricing that scales
              <br />
              with{" "}
              <span className="text-primary">
                <Highlighter action="underline" color="#FF9800">
                  your business.
                </Highlighter>
              </span>
            </h1>

            {/* Subtext with badges */}
            <div className="flex items-center gap-4 flex-wrap max-sm:gap-3">
              <div className="flex items-center gap-2 text-gray-600">
                <svg
                  className="w-5 h-5 max-sm:w-4 max-sm:h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <span className="text-base max-sm:text-sm">
                  7-Day Money-Back Guarantee
                </span>
              </div>
              <div className="flex items-center gap-2 text-gray-600">
                <svg
                  className="w-5 h-5 max-sm:w-4 max-sm:h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M5 13l4 4L19 7"
                  />
                </svg>
                <span className="text-base max-sm:text-sm">
                  Get up to <span className="font-semibold">20% off</span> your
                  first year
                </span>
              </div>
            </div>
          </div>

          {/* Billing Toggle - Removed, only yearly plans */}
        </div>
      </div>

      {/* Pricing Plans */}
      <div className="w-full pb-8 md:pb-16 flex justify-center relative px-4 md:px-6 lg:px-8">
        <div className="w-full max-w-6xl xl:max-w-7xl">
          <div className="flex justify-between items-end gap-4 md:gap-6 flex-wrap flex-row max-md:flex-col max-md:items-stretch">
            {slabs.map((slab, index) => (
              <PricingSlab
                key={slab.slug}
                slab={slab}
                checkingAuth={checkingAuth}
                isFirstSlab={index === 0}
                isLastSlab={index === slabs.length - 1}
                handleCheckout={handleCheckout}
                loadingCheckoutFor={loadingCheckoutFor}
                setLoadingCheckoutFor={setLoadingCheckoutFor}
                scrollToFeatures={scrollToFeatures}
                showSpecialTag={false}
                sizeVariant={slab.sizeVariant}
                currentOrgPlanSlug={currentOrgPlanSlug}
              />
            ))}
          </div>
          {currentOrgPlanSlug && (
            <div className="flex justify-center mt-4 text-gray-600 text-sm">
              Note: The upgrade and downgrade happens at the end of the billing period
            </div>
          )}
        </div>
      </div>


      {/* Features Comparison Table */}
      <div className="w-full py-8 md:py-16 flex justify-center relative px-4 md:px-6 lg:px-8">
        <div
          className="w-full max-w-6xl xl:max-w-7xl"
          ref={featuresFoldRef}
          id="pricing-comparison"
        >
          <div className="mb-8 md:mb-12">
            <h2 className="text-5xl font-bold text-gray-900 mb-4 max-md:text-4xl max-sm:text-3xl">
              Features
            </h2>
            <p className="text-gray-600 text-lg max-sm:text-base">
              All <span className="font-semibold text-gray-900">annually</span>{" "}
              plans give you access to{" "}
              <span className="font-semibold text-gray-900">all</span> of these
              features
            </p>
          </div>

          {/* Comparison Table */}
          <ComparisonTable />
        </div>
      </div>

      {/* Money Back Guarantee & Trust Badges */}
      <MoneyBackGuarantee />

      {/* Testimonials Marquee */}
      <TestimonialsMarquee />

      {/* Additional Trust Badges and Assurance
            <div className="w-full mb-16 flex justify-center">
                <div className="w-full max-w-7xl  max-sm:px-4">
                    <AssuranceSection
                        showStatisticCards={false}
                        showQualities={[
                            'worldClassSupport',
                            'documentationCommunity',
                            'upgradeOrCancelAnytime',
                        ]}
                        assuranceFooter={false}
                    />
                </div>
            </div> */}

      {/* FAQ Section */}
      <FAQSection />

      {/* Call to Action */}
      <CallToActionSection
        preHeading="Ready to start optimizing?"
        heading={`Experience the ${process.env.NEXT_PUBLIC_APP_NAME} Advantage!`}
        postHeading="Join thousands of teams using data-driven testing to improve their results."
      />
    </div>
  );
}
