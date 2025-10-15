"use server";

import { remotePost } from "@/lib/request";
import { ActionResult } from "@/interfaces";

export async function setCurrentOrganizationAction(organizationUuid: string): Promise<ActionResult> {
  try {
    const result = await remotePost<{ success: boolean; data?: any; message?: string }>(`/user/current-organization/${organizationUuid}`, {});

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to set current organization",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred: " + (error as any)?.message,
    };
  }
}

export async function setCurrentWorkspaceAction(workspaceUuid: string): Promise<ActionResult> {
  try {
    const result = await remotePost<{ success: boolean; data?: any; message?: string }>(`/user/current-workspace/${workspaceUuid}`, {});

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to set current workspace",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred: " + (error as any)?.message,
    };
  }
}