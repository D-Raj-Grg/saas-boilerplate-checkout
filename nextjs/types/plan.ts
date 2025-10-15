// Plan and Feature Limit Types based on real API response

export interface PlanFeature {
  name: string;
  type: "limit" | "boolean";
  tracking_scope: "workspace" | "organization";
  limit: number | null;
  current: number;
  remaining: number;
  percentage: number;
  has_feature: boolean;
}

export interface PlanTrial {
  is_trial: boolean;
  is_expired: boolean;
  days_remaining: number;
  ends_at: string;
}

export interface PlanLimits {
  plan: {
    name: string;
    slug: string;
    status: "active" | "inactive" | "none" | string; // string for future statuses like 'revoked', 'suspended', etc.
  };
  features: {
    feature: PlanFeature;
    team_members: PlanFeature;
    workspaces: PlanFeature;
    connections_per_workspace: PlanFeature;
    api_rate_limit: PlanFeature;
    data_retention_days: PlanFeature;
    priority_support: PlanFeature;
  };
  trial?: PlanTrial;
  has_active_plan: boolean;
}

export type FeatureKey = keyof PlanLimits["features"];