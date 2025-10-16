"use server";

import { remoteGet, remotePost } from "@/lib/request";

// Checkout types and actions
export interface CheckoutUrlData {
  plan_slug: string;
  source?: string;
  coupon?: string;
  aff?: string;
  checkout_url?: string;
}

export interface CheckoutUrlResponse {
  success: boolean;
  data?: {
    checkout_url: string;
  };
  message?: string;
  errors?: {
    plan_slug?: string[];
  };
}

export interface CheckoutActionResult {
  success: boolean;
  checkoutUrl?: string;
  error?: string;
}

export async function getCheckoutUrlAction(
  data: CheckoutUrlData
): Promise<CheckoutActionResult> {
  try {
    const result = await remotePost<CheckoutUrlResponse>(
      "/plans/checkout-url",
      data
    );

    if (!result || !result.success || !result.data?.checkout_url) {
      const errorMessage =
        result?.errors?.plan_slug?.[0] ||
        result?.message ||
        "Unable to create checkout link. Please try again.";

      return {
        success: false,
        error: errorMessage,
      };
    }

    return {
      success: true,
      checkoutUrl: result.data.checkout_url,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

// Customer Dashboard types and actions
interface CustomerDashboardResponse {
  success: boolean;
  data?: {
    customer_dashboard_url: string;
  };
  message?: string;
}

export interface CustomerDashboardActionResult {
  success: boolean;
  dashboardUrl?: string;
  error?: string;
}

export async function getCustomerDashboardUrlAction(): Promise<CustomerDashboardActionResult> {
  try {
    const result = await remotePost<CustomerDashboardResponse>("/plans/customer-dashboard-url", {});

    if (!result || !result.success || !result.data?.customer_dashboard_url) {
      return {
        success: false,
        error: result?.message || "Unable to create customer dashboard link. Please try again.",
      };
    }

    return {
      success: true,
      dashboardUrl: result.data.customer_dashboard_url,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

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
  checkout_url: string | null;
  upgrade_downgrade_url: string | null;
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
