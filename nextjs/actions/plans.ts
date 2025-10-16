"use server";

import { remoteGet, remotePost } from "@/lib/request";

// Plans and Features types
export interface Plan {
  uuid: string;
  group: string;
  name: string;
  slug: string;
  description: string;
  price: number;
  max_price: number;
  billing_cycle: string;
  priority: number;
  is_free: boolean;
}

export interface Feature {
  feature: string;
  name: string;
  description: string;
  type: "limit" | "boolean";
  category: string;
  period: string;
}

export interface FeatureLimit {
  value: string;
  type: "limit" | "boolean";
}

export interface PlansResponse {
  success: boolean;
  data?: {
    plans: Record<string, Plan>;
    features: Feature[];
    limits: Record<string, Record<string, FeatureLimit>>;
  };
  message?: string;
}

export interface PlansActionResult {
  success: boolean;
  plans?: Record<string, Plan>;
  features?: Feature[];
  limits?: Record<string, Record<string, FeatureLimit>>;
  error?: string;
}

export async function getPlansAction(): Promise<PlansActionResult> {
  try {
    const result = await remoteGet<PlansResponse>("/plans");

    if (!result || !result.success || !result.data) {
      return {
        success: false,
        error: result?.message || "Unable to fetch plans. Please try again.",
      };
    }

    return {
      success: true,
      plans: result.data.plans,
      features: result.data.features,
      limits: result.data.limits,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}
