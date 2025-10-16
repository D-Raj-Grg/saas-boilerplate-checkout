"use server";

import { remoteGet, remotePut } from "@/lib/request";

export interface WorkspaceSettings {
  enable_subdomain_cookies: boolean;
}

export interface PlanLimits {
  total_team_members: number;
}

export interface SettingsResponse {
  workspace_uuid: string;
  settings: WorkspaceSettings;
  plan_limits: PlanLimits;
  created_at: string;
  updated_at: string;
}

export async function getWorkspaceSettings() {
  try {
    const data = await remoteGet<{ success: boolean; data: SettingsResponse }>("/workspace/settings");
    return {
      success: true,
      data: data.data,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function updateWorkspaceSettings(settings: WorkspaceSettings) {
  try {
    const data = await remotePut<{ success: boolean; data?: SettingsResponse; message?: string }>("/workspace/settings", { settings });

    // Check if the API call was successful
    if (!data.success) {
      return {
        success: false,
        error: data.message || "Failed to update workspace settings",
      };
    }

    return {
      success: true,
      data: data.data,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}