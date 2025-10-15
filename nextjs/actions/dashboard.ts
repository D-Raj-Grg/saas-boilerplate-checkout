"use server";

import { remoteGet } from "@/lib/request";
import { DashboardResponse, DashboardActionResult } from "@/interfaces";

export async function getDashboardDataAction(): Promise<DashboardActionResult> {
  try {
    const result = await remoteGet<DashboardResponse>("/dashboard");

    if (!result) {
      return {
        success: false,
        error: "Failed to fetch dashboard data",
      };
    }

    if (!result.success) {
      return {
        success: false,
        error: result.message || "Failed to fetch dashboard data",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An error occurred while fetching dashboard data",
    };
  }
}