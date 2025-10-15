"use client";

import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { usePlanLimits } from "@/stores/user-store";
import { AlertTriangle, Sparkles, X } from "lucide-react";
import { useState } from "react";

interface PlanLimitBannerProps {
  /**
   * Which features to check for limits. If not provided, checks all features.
   */
  features?: string[];
  /**
   * Percentage threshold to show warning (default: 80%)
   */
  warningThreshold?: number;
  /**
   * Show banner even when not at limit but approaching it
   */
  showWarnings?: boolean;
}

export function PlanLimitBanner({
  features,
  warningThreshold = 80,
  showWarnings = true
}: PlanLimitBannerProps) {
  const planLimits = usePlanLimits();
  const [dismissed, setDismissed] = useState(false);

  if (!planLimits || dismissed) {
    return null;
  }

  // Defensive check: ensure features object exists and is not empty
  if (!planLimits.features || Object.keys(planLimits.features).length === 0) {
    return null;
  }

  // Check which features are at or near their limits
  const featuresToCheck = features || Object.keys(planLimits.features);
  const limitedFeatures: Array<{
    name: string;
    current: number;
    limit: number;
    percentage: number;
    isAtLimit: boolean;
    isNearLimit: boolean;
  }> = [];

  featuresToCheck.forEach(featureKey => {
    const feature = planLimits.features[featureKey as keyof typeof planLimits.features];
    if (!feature || !feature.has_feature) return;

    // Skip unlimited features
    if (feature.limit === null || feature.limit === -1) return;

    const isAtLimit = feature.remaining === 0;
    const isNearLimit = feature.percentage >= warningThreshold;

    if (isAtLimit || (showWarnings && isNearLimit)) {
      limitedFeatures.push({
        name: feature.name,
        current: feature.current,
        limit: feature.limit,
        percentage: feature.percentage,
        isAtLimit,
        isNearLimit: isNearLimit && !isAtLimit
      });
    }
  });

  if (limitedFeatures.length === 0) {
    return null;
  }

  // Determine the most critical issue
  const hasLimitReached = limitedFeatures.some(f => f.isAtLimit);
  const criticalFeatures = limitedFeatures.filter(f => f.isAtLimit);
  const warningFeatures = limitedFeatures.filter(f => f.isNearLimit);

  const getTitle = () => {
    if (hasLimitReached) {
      if (criticalFeatures.length === 1) {
        return `${criticalFeatures[0].name} limit reached`;
      }
      return `${criticalFeatures.length} plan limits reached`;
    } else {
      if (warningFeatures.length === 1) {
        return `Approaching ${warningFeatures[0].name.toLowerCase()} limit`;
      }
      return "Approaching plan limits";
    }
  };

  const getDescription = () => {
    if (hasLimitReached) {
      const featureList = criticalFeatures.map(f =>
        `${f.name} (${f.current}/${f.limit})`
      ).join(", ");
      return `You've reached your limit for: ${featureList}. Upgrade to continue using these features.`;
    } else {
      const featureList = warningFeatures.map(f =>
        `${f.name} (${f.current}/${f.limit})`
      ).join(", ");
      return `You're approaching your limits for: ${featureList}. Consider upgrading to avoid interruptions.`;
    }
  };

  return (
    <Alert className="border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-800 dark:bg-orange-950 dark:text-orange-200">
      <AlertTriangle className="h-4 w-4" />
      <div className="flex items-start justify-between w-full">
        <div className="flex-1">
          <AlertTitle className="text-orange-800 dark:text-orange-200">
            {getTitle()}
          </AlertTitle>
          <AlertDescription className="text-orange-700 dark:text-orange-300 mt-1">
            {getDescription()}
          </AlertDescription>
          <div className="flex gap-2 mt-3">
            <Button
              size="sm"
              onClick={() => window.location.href = '/pricing'}
              className="bg-orange-600 hover:bg-orange-700 text-white"
            >
              <Sparkles className="h-3 w-3 mr-1" />
              Upgrade Plan
            </Button>
            {showWarnings && !hasLimitReached && (
              <Button
                size="sm"
                variant="ghost"
                onClick={() => setDismissed(true)}
                className="text-orange-700 hover:text-orange-800 hover:bg-orange-100 dark:text-orange-300 dark:hover:text-orange-200 dark:hover:bg-orange-900"
              >
                Dismiss
              </Button>
            )}
          </div>
        </div>
        {showWarnings && !hasLimitReached && (
          <Button
            size="sm"
            variant="ghost"
            onClick={() => setDismissed(true)}
            className="text-orange-700 hover:text-orange-800 hover:bg-orange-100 dark:text-orange-300 dark:hover:text-orange-200 dark:hover:bg-orange-900 ml-2"
          >
            <X className="h-4 w-4" />
          </Button>
        )}
      </div>
    </Alert>
  );
}