"use server";

import { remotePost } from "@/lib/request";
import { ConnectionInitiateResult, ConnectionInitiateResponse } from "@/interfaces";

export async function initiateConnectionAction(redirectUrl: string): Promise<ConnectionInitiateResult> {
  try {
    const result = await remotePost<ConnectionInitiateResponse>("/connections/initiate", {
      redirect_url: redirectUrl
    });

    if (!result) {
      return {
        success: false,
        error: "Failed to initiate connection"
      };
    }

    if (!result.success) {
      return {
        success: false,
        error: result.message || "Failed to initiate connection"
      };
    }

    return {
      success: true,
      message: result.message,
      data: result.data
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred. Please try again."
    };
  }
}