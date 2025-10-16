"use server";

import { remoteGet, remotePost, remoteDelete, remotePut, remotePatch } from "@/lib/request";
import { ActionResult, OrganizationStatsResponse, ApiResponse } from "@/interfaces";
import { revalidatePath } from "next/cache";

export async function getOrganizationsAction(): Promise<ActionResult> {
  try {
    const result = await remoteGet<{ success: boolean; data?: any; message?: string }>("/organization");

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch organizations",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function createOrganizationAction(data: {
  name: string;
  description?: string;
  workspace_name?: string;
  workspace_description?: string;
  plan_id?: number;
  settings?: Record<string, any>;
}): Promise<ActionResult> {
  try {
    const result = await remotePost<{ success: boolean; data?: any; message?: string }>("/organization", data);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to create organization",
      };
    }

    revalidatePath("/organization");
    return {
      success: true,
      data: result.data,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export async function getOrganizationAction(_uuid: string): Promise<ActionResult> {
  try {
    const result = await remoteGet<{ success: boolean; data?: any; message?: string }>("/organization");

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch organization",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function updateOrganizationAction(
  uuid: string,
  data: {
    name?: string;
    description?: string;
    settings?: Record<string, any>;
  }
): Promise<ActionResult> {
  try {
    const result = await remotePut<{ success: boolean; data?: any; message?: string }>("/organization", data);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to update organization",
      };
    }

    revalidatePath("/organization");
    return {
      success: true,
      data: result.data,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function deleteOrganizationAction(uuid: string): Promise<ActionResult> {
  try {
    const result = await remoteDelete<{ success: boolean; data?: any; message?: string }>(`/organization/${uuid}`);

    if (!result || (result && !result.success)) {
      return {
        success: false,
        error: result?.message || "Failed to delete organization",
      };
    }

    revalidatePath("/organization");
    return { success: true };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function getOrganizationStatisticsAction(uuid: string): Promise<ActionResult> {
  try {
    const result = await remoteGet<{ success: boolean; data?: any; message?: string }>(`/organization/${uuid}/statistics`);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch organization statistics",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function getOrganizationUsageAction(uuid: string): Promise<ActionResult> {
  try {
    const result = await remoteGet<{ success: boolean; data?: any; message?: string }>(`/organization/${uuid}/usage`);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch organization usage",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function transferOrganizationOwnershipAction(
  uuid: string,
  newOwnerId: number
): Promise<ActionResult> {
  try {
    const result = await remotePost<{ success: boolean; data?: any; message?: string }>(`/organization/${uuid}/transfer-ownership`, { new_owner_uuid: newOwnerId });

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to transfer ownership",
      };
    }

    revalidatePath("/organization");
    return { success: true };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function getOrganizationMembersAction(): Promise<ActionResult> {
  try {
    const result = await remoteGet<ApiResponse<any>>("/organization/members");

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch organization members",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function removeOrganizationMemberAction(userUuid: string): Promise<ActionResult> {
  try {
    const result = await remoteDelete<ApiResponse<any>>(`/organization/members/${userUuid}`);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to remove member",
      };
    }

    revalidatePath("/organizations");
    return { success: true };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function changeOrganizationMemberRoleAction(
  userUuid: string,
  data: {
    role: "admin" | "member";
    workspace_assignments?: Array<{
      workspace_id: string;
      role: "manager" | "viewer";
    }>;
  }
): Promise<ActionResult> {
  try {
    const result = await remotePatch<ApiResponse<any>>(`/organization/members/${userUuid}/role`, data);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to change member role",
      };
    }

    revalidatePath("/organizations");
    return { success: true };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function getOrganizationStatsAction(): Promise<ActionResult> {
  try {
    const result = await remoteGet<OrganizationStatsResponse>("/organization/stats");

    if (!result || !result.success) {
      return {
        success: false,
        error: "Failed to fetch organization stats",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}