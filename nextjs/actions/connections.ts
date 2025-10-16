"use server";

import { remoteGet, remoteDelete, remotePost } from "@/lib/request";
import {
  ConnectionsListResult,
  DeleteConnectionResult,
  ConnectionsListResponse,
  SyncConnectionResponse,
  SyncConnectionResult
} from "@/interfaces";

export async function getConnectionsAction(): Promise<ConnectionsListResult> {
  try {
    const result = await remoteGet<ConnectionsListResponse>("/connections");

    if (!result) {
      return {
        success: false,
        error: "Failed to fetch connections"
      };
    }

    if (!result.success) {
      return {
        success: false,
        error: result.message || "Failed to fetch connections"
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

export async function deleteConnectionAction(connectionId: string): Promise<DeleteConnectionResult> {
  try {
    const result = await remoteDelete<{ success: boolean; message: string }>(`/connections/${connectionId}`);

    if (!result) {
      return {
        success: false,
        error: "Failed to delete connection"
      };
    }

    if (!result.success) {
      return {
        success: false,
        error: result.message || "Failed to delete connection"
      };
    }

    return {
      success: true,
      message: result.message
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred. Please try again."
    };
  }
}

export async function syncConnectionAction(connectionId: string): Promise<SyncConnectionResult> {
  try {
    const result = await remotePost<SyncConnectionResponse>("/connections/sync", {
      connection_uuid: connectionId
    });

    if (!result) {
      return {
        success: false,
        error: "Failed to sync connection"
      };
    }

    if (!result.success) {
      return {
        success: false,
        error: result.message || "Failed to sync connection"
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