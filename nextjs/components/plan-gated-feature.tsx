"use client";

import React from "react";
import { Tooltip, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import * as TooltipPrimitive from "@radix-ui/react-tooltip";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { usePlanLimits } from "@/stores/user-store";
import { FeatureKey } from "@/types/plan";
import { Lock, Sparkles } from "lucide-react";

interface PlanGatedFeatureProps {
  feature: FeatureKey;
  canPerformAction?: boolean; // Optional override
  children: React.ReactNode;
  fallback?: React.ReactNode; // Custom fallback instead of disabled children
  showTooltip?: boolean; // Default true
  upgradeUrl?: string; // Custom upgrade URL
  tooltipMessage?: string; // Custom tooltip message
  className?: string; // Custom className for the wrapper div
}

/**
 * A simple wrapper component that conditionally renders children based on plan limitations.
 *
 * @example
 * // Basic usage - automatically checks feature availability
 * <PlanGatedFeature feature="feature">
 *   <Button>Add Feature</Button>
 * </PlanGatedFeature>
 *
 * @example
 * // With custom logic
 * <PlanGatedFeature
 *   feature="feature"
 *   canPerformAction={remainingFeatures > 0}
 * >
 *   <Button>Create Feature</Button>
 * </PlanGatedFeature>
 *
 * @example
 * // With custom fallback
 * <PlanGatedFeature
 *   feature="webhooks"
 *   fallback={<div>Webhooks require Pro plan</div>}
 * >
 *   <WebhookSettings />
 * </PlanGatedFeature>
 */
export function PlanGatedFeature({
  feature,
  canPerformAction,
  children,
  fallback,
  showTooltip = true,
  upgradeUrl,
  tooltipMessage,
  className
}: PlanGatedFeatureProps) {
  const planLimits = usePlanLimits();

  // If no plan limits, allow everything (development mode)
  if (!planLimits) {
    return <>{children}</>;
  }

  // Defensive check: ensure features object exists
  if (!planLimits.features || Object.keys(planLimits.features).length === 0) {
    // If plan is not active, block access
    if (planLimits.plan.status !== "active") {
      const message = tooltipMessage || `This feature requires an active plan. Please upgrade to continue.`;
      return renderDisabledState(children, message, fallback, showTooltip, upgradeUrl, className, "Plan Required");
    }
    // Otherwise allow (shouldn't happen but be safe)
    return <>{children}</>;
  }

  const featureData = planLimits.features[feature];

  // If feature doesn't exist, allow (should not happen)
  if (!featureData) {
    return <>{children}</>;
  }

  // Determine if action is allowed
  const isAllowed = canPerformAction !== undefined
    ? canPerformAction
    : featureData.has_feature && (featureData.limit === null || featureData.limit === -1 || featureData.remaining > 0);

  // If allowed, render children normally
  if (isAllowed) {
    return <>{children}</>;
  }

  // If not allowed and custom fallback provided, use it
  if (fallback) {
    return <>{fallback}</>;
  }

  // Generate default tooltip message based on plan status
  const defaultTooltipMessage = planLimits.plan.status !== "active"
    ? `Your plan is inactive. Please upgrade to continue.`
    : !featureData.has_feature
      ? `${featureData.name} is not available in your ${planLimits.plan.name} plan. Upgrade to unlock this feature.`
      : featureData.limit === null || featureData.limit === -1
        ? `${featureData.name} is unlimited in your ${planLimits.plan.name} plan.`
        : `You've reached your limit of ${featureData.limit} ${featureData.name.toLowerCase()}. Upgrade for higher limits.`;

  const message = tooltipMessage || defaultTooltipMessage;

  return renderDisabledState(children, message, fallback, showTooltip, upgradeUrl, className, "Plan Limit Reached");
}

// Helper function to render disabled state with or without tooltip
function renderDisabledState(
  children: React.ReactNode,
  message: string,
  fallback: React.ReactNode | undefined,
  showTooltip: boolean,
  upgradeUrl: string | undefined,
  className: string | undefined,
  tooltipTitle: string
) {
  // If no tooltip requested, just show disabled children
  if (!showTooltip) {
    return (
      <div className={cn("opacity-50 cursor-not-allowed pointer-events-none", className)}>
        {children}
      </div>
    );
  }

  // Render with tooltip
  return (
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger asChild>
          <div className={cn("inline-flex", className)}>
            {renderDisabledChildren(children, className)}
          </div>
        </TooltipTrigger>
        <TooltipPrimitive.Portal>
          <TooltipPrimitive.Content
            side="top"
            sideOffset={8}
            className={cn(
              "max-w-xs bg-gray-900 text-white shadow-lg border border-gray-700 rounded-lg px-3 py-2 text-sm font-medium",
              "animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95",
              "data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2",
              "z-50 w-fit origin-[--radix-tooltip-content-transform-origin]"
            )}
          >
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <Lock className="h-4 w-4" />
                <span className="font-medium">{tooltipTitle}</span>
              </div>
              <p className="text-sm">{message}</p>
              <div className="pt-2 border-t border-gray-700">
                <Button
                  size="sm"
                  onClick={(e) => {
                    e.stopPropagation();
                    window.location.href = upgradeUrl || '/pricing';
                  }}
                  className="w-full text-xs font-medium"
                >
                  <Sparkles className="h-3 w-3 mr-1" />
                  Upgrade Plan
                </Button>
              </div>
            </div>
          </TooltipPrimitive.Content>
        </TooltipPrimitive.Portal>
      </Tooltip>
    </TooltipProvider>
  );
}

// Helper function to render children as disabled
function renderDisabledChildren(children: React.ReactNode, className: string | undefined) {
  return React.Children.map(children, child => {
    if (React.isValidElement(child)) {
      // If it's a button or input-like element, add disabled prop
      const elementType = child.type;
      if (
        elementType === 'button' ||
        elementType === Button ||
        elementType === 'input' ||
        elementType === 'select' ||
        elementType === 'textarea' ||
        (typeof child.props === 'object' && child.props !== null && 'disabled' in (child.props as Record<string, unknown>))
      ) {
        return React.cloneElement(child as React.ReactElement<{ disabled?: boolean; className?: string }>, {
          disabled: true,
          className: `${(child.props as { className?: string }).className || ''} cursor-not-allowed`
        });
      }
    }
    // For other elements, wrap in disabled div
    return (
      <div className={cn("opacity-50 cursor-not-allowed pointer-events-none", className)}>
        {child}
      </div>
    );
  });
}
