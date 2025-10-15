"use client";

import { cn } from "@/lib/utils";
import { ArrowRight } from "lucide-react";
import { useUser, useSelectedOrganization } from "@/stores/user-store";
import { Button } from "@/components/ui/button";
import Link from "next/link";

interface PricingFeature {
    name: string;
    tooltip?: React.ReactNode;
    disabled?: boolean;
    suffixEmoji?: string;
}

interface PricingSlab {
    name: React.ReactNode;
    slug: string;
    description?: string;
    costMonthly?: number;
    costAnnually?: number;
    cost?: number;
    ctaText: string;
    isCustomPrice?: boolean;
    features: {
        [key: string]: (string | PricingFeature)[];
    };
    specialTag?: string;
    durationTag?: string;
    oldPrice?: {
        monthly?: number;
        yearly?: string;
        duration?: string;
    };
    currencySuperscript?: boolean;
    billingText?: string;
    billingType?: string;
    isRecommended?: boolean;
    allowMultiplePurchase?: boolean;
    secondSpecialTag?: string;
    secondSpecialTagClassName?: string;
    onClick?: (setLoading: (slug: string | null) => void) => void;
}

interface PricingSlabProps {
    slab: PricingSlab;
    checkingAuth?: boolean;
    slabClassName?: string;
    isFirstSlab?: boolean;
    isLastSlab?: boolean;
    handleCheckout?: (params: {
        slug: string;
        isActivePlan: boolean;
        billingType?: string;
    }) => void;
    loadingCheckoutFor?: string | null;
    setLoadingCheckoutFor?: (slug: string | null) => void;
    scrollToFeatures?: () => void;
    showSpecialTag?: boolean;
    showOptionsDropdown?: boolean;
    featureNameClassName?: string;
    pricingTextClassName?: string;
    ctaClassName?: string;
    sendPaidUserToBilling?: boolean;
    sizeVariant?: 'small' | 'medium' | 'large';
    currentOrgPlanSlug?: string | null;
}

// Map plan names to gradient colors using project teal and orange
const planGradients: Record<string, string> = {
    'free': 'from-gray-50 to-gray-100',
    'starter': 'from-teal-50 via-teal-100 to-cyan-50',
    'pro': 'from-orange-50 via-orange-100 to-amber-50',
    'business': 'from-teal-100 via-orange-100 to-amber-100',
};

export function PricingSlab({
    slab: {
        name,
        slug,
        cost,
        isCustomPrice,
        features,
        oldPrice,
        onClick,
        billingType,
        allowMultiplePurchase = false,
    },
    checkingAuth = false,
    handleCheckout,
    loadingCheckoutFor,
    setLoadingCheckoutFor,
    sizeVariant = 'medium',
    currentOrgPlanSlug,
}: PricingSlabProps) {
    const user = useUser();
    const selectedOrganization = useSelectedOrganization();
    const isFreePlan = slug === 'free' || slug.startsWith('free-');

    // Check if this is the current plan (compare full slugs)
    const isCurrentPlan = !!user && !!selectedOrganization && currentOrgPlanSlug === slug;
    const hasPurchasedThisPlan = isCurrentPlan && !allowMultiplePurchase;

    // Determine if this is an upgrade or downgrade
    const planPriority: Record<string, number> = {
        'free': 0,
        'starter': 1,
        'pro': 2,
        'business': 3,
    };

    // Extract base plan name from slug (e.g., "business-yearly" -> "business")
    const currentPlanBase = currentOrgPlanSlug?.split('-')[0] || 'free';
    const targetPlanBase = slug?.split('-')[0] || 'free';

    const currentPriority = planPriority[currentPlanBase] || 0;
    const targetPriority = planPriority[targetPlanBase] || 0;

    const isUpgrade = targetPriority > currentPriority;
    const isDowngrade = targetPriority < currentPriority;

    const handleSlabClick = () => {
        if (typeof onClick === 'function') {
            setLoadingCheckoutFor?.(slug);
            onClick(setLoadingCheckoutFor!);
            return;
        }

        handleCheckout?.({
            slug,
            isActivePlan: hasPurchasedThisPlan,
            billingType,
        });
    };

    // Get gradient for this plan (use base slug for gradient lookup)
    const planBaseSlug = slug?.split('-')[0] || slug;
    const gradient = planGradients[planBaseSlug] || 'from-gray-50 to-gray-100';

    // Use the cost directly from the plan
    const currentPrice = cost;

    // Determine old price for display
    const displayOldPrice = oldPrice?.yearly || oldPrice?.monthly || null;

    // Get visitor count from features for display
    const visitorFeature = features?.["What's included:"]?.[0];
    const rawVisitorText = typeof visitorFeature === 'string' ? visitorFeature : visitorFeature?.name;
    // Clean up visitor text - remove "Yearly" and keep just the number + "Visitors"
    const visitorText = rawVisitorText?.replace(/Yearly\s+/gi, '');

    // Size variant styles - similar heights, slight width differences

    // Text size based on variant
    const textSizeStyles = {
        small: 'text-5xl',
        medium: 'text-6xl',
        large: 'text-7xl',
    };

    // Mark as used
    void billingType;

    return (
        <div
            className={cn(
                `bg-gradient-to-br ${gradient} rounded-[3rem] p-8 md:p-10 flex flex-col min-h-fit w-full flex-1`,

            )}
        >
            {/* Plan Name - Progressive Size */}
            <h2 className={cn(
                "font-bold text-gray-900 mb-4 leading-tight max-md:text-4xl max-sm:text-3xl",
                textSizeStyles[sizeVariant]
            )}>
                {name}
            </h2>

            {/* Features Summary */}
            <p className="text-gray-600 mb-6 text-base max-sm:text-sm">
                Access to{" "}
                <Link
                    href="#pricing-comparison"
                    className="underline decoration-2 underline-offset-4 hover:text-gray-900 transition-colors cursor-pointer"
                    scroll={true}
                >
                    all features
                </Link>
            </p>

            {/* Visitor Info */}
            {visitorText && (
                <p className="text-gray-900 text-lg mb-10 max-sm:text-base max-sm:mb-6">
                    Up to{" "}
                    <span className={cn(
                        "font-semibold rounded-3xl",
                        // Check if slug contains 'business' for yellow highlight
                        slug.includes('business') ? "bg-yellow-300 px-2 py-1" : "bg-white px-2 py-1"
                    )}>
                        {visitorText}
                    </span>
                </p>
            )}

            {/* Pricing - Old and New Price on Same Line */}
            <div className="mb-8 max-sm:mb-6">
                <div className="flex items-baseline gap-2 flex-wrap">
                    {!isFreePlan && displayOldPrice && (
                        <span className="text-gray-400 line-through text-xl max-sm:text-lg">
                            ${displayOldPrice}
                        </span>
                    )}
                    <span className="text-[#005F5A] text-6xl font-bold max-md:text-5xl max-sm:text-4xl">
                        {isCustomPrice ? 'Custom' : `$${cost || currentPrice || 0}`}
                    </span>
                    <span className="text-gray-600 text-xl max-sm:text-base">
                        /year
                    </span>
                </div>
            </div>

            {/* CTA Button */}
            <Button
                className="mt-auto bg-white hover:bg-gray-900 rounded-2xl px-6 md:px-8 py-6 md:py-8 flex items-center justify-between text-lg md:text-xl font-semibold text-gray-900 hover:text-white shadow-xl hover:shadow-2xl transition-all duration-100 ease-out disabled:opacity-50 disabled:cursor-not-allowed group"
                disabled={checkingAuth || !!loadingCheckoutFor || hasPurchasedThisPlan}
                onClick={handleSlabClick}
            >
                <span>
                    {loadingCheckoutFor === slug
                        ? 'Loading...'
                        : hasPurchasedThisPlan
                            ? 'Current Plan'
                            : (!user || currentPlanBase === 'free')
                                ? 'Purchase'
                                : isUpgrade
                                    ? 'Upgrade'
                                    : isDowngrade
                                        ? 'Downgrade'
                                        : 'Purchase'}
                </span>
                <ArrowRight className="w-5 h-5 md:w-6 md:h-6 ml-3 md:ml-4 transition-all duration-500 ease-out group-hover:translate-x-1" />
            </Button>
        </div>
    );
}
