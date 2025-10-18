"use server";

import { cookies } from "next/headers";
import { remoteGet, remotePost } from "@/lib/request";

// Payment Gateway Types
export type PaymentGateway = "esewa" | "khalti" | "stripe" | "mock";

// Payment Initiation
export interface InitiatePaymentData {
  plan_slug: string;
  gateway: PaymentGateway;
  guest_first_name?: string;
  guest_last_name?: string;
  guest_email?: string;
  guest_password?: string;
}

export interface InitiatePaymentResponse {
  success: boolean;
  data?: {
    payment_uuid: string;
    payment_url: string;
    gateway: PaymentGateway;
    form_params?: Record<string, any>; // For eSewa form parameters
  };
  message?: string;
  errors?: {
    plan_slug?: string[];
    gateway?: string[];
  };
}

export interface InitiatePaymentResult {
  success: boolean;
  paymentUuid?: string;
  paymentUrl?: string;
  formParams?: Record<string, any>; // For eSewa form parameters
  gateway?: PaymentGateway;
  error?: string;
}

export async function initiatePaymentAction(
  data: InitiatePaymentData
): Promise<InitiatePaymentResult> {
  try {
    // All gateways now return JSON uniformly
    const result = await remotePost<InitiatePaymentResponse>(
      "/payments/initiate",
      data
    );

    if (!result || !result.success || !result.data) {
      const errorMessage =
        result?.errors?.plan_slug?.[0] ||
        result?.errors?.gateway?.[0] ||
        result?.message ||
        "Unable to initiate payment. Please try again.";

      return {
        success: false,
        error: errorMessage,
      };
    }

    return {
      success: true,
      paymentUuid: result.data.payment_uuid,
      paymentUrl: result.data.payment_url,
      formParams: result.data.form_params, // Will be populated for eSewa
      gateway: result.data.gateway,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred while initiating payment",
    };
  }
}

// Payment Verification
export interface VerifyPaymentResponse {
  success: boolean;
  data?: {
    payment_status: string;
    plan_attached: boolean;
  };
  message?: string;
}

export interface VerifyPaymentResult {
  success: boolean;
  paymentStatus?: string;
  planAttached?: boolean;
  error?: string;
}

export async function verifyPaymentAction(
  paymentUuid: string,
  verificationParams?: Record<string, string>
): Promise<VerifyPaymentResult> {
  try {
    // Build query string from verification params
    const queryString = verificationParams
      ? "?" + new URLSearchParams(verificationParams).toString()
      : "";

    const result = await remoteGet<VerifyPaymentResponse>(
      `/payments/${paymentUuid}/verify${queryString}`
    );

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Payment verification failed",
      };
    }

    return {
      success: true,
      paymentStatus: result.data?.payment_status,
      planAttached: result.data?.plan_attached,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred during payment verification",
    };
  }
}

// Payment Status
export interface PaymentStatusResponse {
  success: boolean;
  data?: {
    payment_uuid: string;
    status: string;
    gateway: string;
    amount: number;
    currency: string;
    created_at: string;
  };
  message?: string;
}

export interface PaymentStatusResult {
  success: boolean;
  payment?: {
    uuid: string;
    status: string;
    gateway: string;
    amount: number;
    currency: string;
    createdAt: string;
  };
  error?: string;
}

export async function getPaymentStatusAction(
  paymentUuid: string
): Promise<PaymentStatusResult> {
  try {
    const result = await remoteGet<PaymentStatusResponse>(
      `/payments/${paymentUuid}/status`
    );

    if (!result || !result.success || !result.data) {
      return {
        success: false,
        error: result?.message || "Unable to fetch payment status",
      };
    }

    return {
      success: true,
      payment: {
        uuid: result.data.payment_uuid,
        status: result.data.status,
        gateway: result.data.gateway,
        amount: result.data.amount,
        currency: result.data.currency,
        createdAt: result.data.created_at,
      },
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

// Payment History
export interface PaymentHistoryItem {
  uuid: string;
  gateway: string;
  amount: number;
  currency: string;
  status: string;
  plan_name: string;
  created_at: string;
}

export interface PaymentHistoryResponse {
  success: boolean;
  data?: PaymentHistoryItem[];
  message?: string;
}

export interface PaymentHistoryResult {
  success: boolean;
  payments?: PaymentHistoryItem[];
  error?: string;
}

export async function getPaymentHistoryAction(): Promise<PaymentHistoryResult> {
  try {
    const result = await remoteGet<PaymentHistoryResponse>("/payments/history");

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Unable to fetch payment history",
      };
    }

    return {
      success: true,
      payments: result.data || [],
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}
