"use client";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { usePlanLimits } from "@/stores/user-store";
import { Button } from "@/components/ui/button";
import { Sparkles } from "lucide-react";

interface UsageStatsDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

interface UsageStatProps {
  label: string;
  current: number;
  limit: number | null;
  percentage: number;
}

function UsageStat({ label, current, limit, percentage }: UsageStatProps) {
  const limitDisplay = limit === null || limit === -1 ? "Unlimited" : limit.toString();
  const progressWidth = limit === null || limit === -1 ? 0 : percentage;

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between text-sm">
        <span className="text-muted-foreground">{label}</span>
        <span className="font-semibold">
          {current} / {limitDisplay}
        </span>
      </div>
      <div className="w-full bg-gray-100 rounded-full h-2">
        <div
          className="bg-black h-2 rounded-full transition-all duration-500"
          style={{
            width: `${progressWidth}%`
          }}
        ></div>
      </div>
    </div>
  );
}

export function UsageStatsDialog({ open, onOpenChange }: UsageStatsDialogProps) {
  const planLimits = usePlanLimits();

  if (!planLimits || !planLimits.plan) {
    return null;
  }

  // Defensive check: ensure features object exists and is not empty
  const hasFeatures = planLimits.features && Object.keys(planLimits.features).length > 0;

  const stats = hasFeatures ? [
    {
      label: "Team Members",
      feature: planLimits.features.team_members,
    },
  ] : [];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Usage Statistics</DialogTitle>
          <DialogDescription>
            Your current usage for the {planLimits.plan.name} plan
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6 py-4">
          {hasFeatures ? (
            stats.map((stat) => (
              <UsageStat
                key={stat.label}
                label={stat.label}
                current={stat.feature?.current || 0}
                limit={stat.feature?.limit}
                percentage={stat.feature?.percentage || 0}
              />
            ))
          ) : (
            <div className="text-center py-8 text-muted-foreground">
              <p className="text-sm">No usage data available.</p>
              <p className="text-xs mt-1">
                {planLimits.plan?.status !== "active"
                  ? "Your plan is inactive. Please upgrade to continue."
                  : "Upgrade to access usage statistics."}
              </p>
            </div>
          )}
        </div>

        <div className="flex justify-end gap-3 pt-4 border-t">
          <Button
            variant="outline"
            onClick={() => onOpenChange(false)}
          >
            Close
          </Button>
          <Button
            onClick={() => {
              window.location.href = '/pricing';
            }}
          >
            <Sparkles className="h-4 w-4 mr-2" />
            Upgrade Plan
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}