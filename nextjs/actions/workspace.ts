"use server";

import { remoteGet, remotePost, remoteDelete, remotePatch, remotePut } from "@/lib/request";
import { ActionResult, ApiResponse } from "@/interfaces";
import { revalidatePath } from "next/cache";

export async function getWorkspacesAction(): Promise<ActionResult> {
  try {
    const result = await remoteGet<ApiResponse>("/organization/workspaces");

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch workspaces",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function createWorkspaceAction(data: {
  name: string;
  description?: string;
  organization_uuid: string;
  settings?: Record<string, any>;
}): Promise<ActionResult> {
  try {
    const result = await remotePost<ApiResponse>("/organization/workspace", data);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || result?.error || "Failed to create workspace",
      };
    }

    revalidatePath("/organization/workspaces");
    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: `An unexpected error occurred: ${(error as any)?.message || error}`,
    };
  }
}

export async function getWorkspaceAction(): Promise<ActionResult> {
  try {
    const result = await remoteGet<ApiResponse>("/workspace");

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch workspace",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function updateWorkspaceAction(
  data: {
    name?: string;
    description?: string;
    settings?: Record<string, any>;
  }
): Promise<ActionResult> {
  try {
    const result = await remotePut<ApiResponse>("/workspace", data);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to update workspace",
      };
    }

    revalidatePath("/workspace");
    revalidatePath("/organization/workspaces");
    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function deleteWorkspaceAction(): Promise<ActionResult> {
  try {
    const result = await remoteDelete<ApiResponse>("/workspace");

    if (!result || (result && !result.success)) {
      return {
        success: false,
        error: result?.message || "Failed to delete workspace",
      };
    }

    revalidatePath("/organization/workspaces");
    return { success: true };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function getWorkspaceMembersAction(): Promise<ActionResult> {
  try {
    const result = await remoteGet<ApiResponse>("/workspace/members");

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch workspace members",
      };
    }

    // Return the API data directly since it's already in the expected format
    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function removeWorkspaceMemberAction(
  userUuid: string
): Promise<ActionResult> {
  try {
    const result = await remoteDelete<ApiResponse>(`/workspace/members/${userUuid}`);

    if (!result || (result && !result.success)) {
      return {
        success: false,
        error: result?.message || "Failed to remove member",
      };
    }

    revalidatePath("/workspace/members");
    return { success: true };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function changeWorkspaceMemberRoleAction(
  userUuid: string,
  role: string
): Promise<ActionResult> {
  try {
    const result = await remotePatch<ApiResponse>(`/workspace/members/${userUuid}/role`, { role });

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to change role",
      };
    }

    revalidatePath("/workspace/members");
    return { success: true };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}


export async function transferWorkspaceOwnershipAction(
  newOwnerId: number
): Promise<ActionResult> {
  try {
    const result = await remotePost<ApiResponse>("/workspace/transfer-ownership", { new_owner_uuid: newOwnerId });

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to transfer ownership",
      };
    }

    revalidatePath("/workspace");
    revalidatePath("/organization/workspaces");
    return { success: true };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function duplicateWorkspaceAction(
  data: {
    name: string;
    description?: string;
    copy_members?: boolean;
  }
): Promise<ActionResult> {
  try {
    const result = await remotePost<ApiResponse>("/workspace/duplicate", data);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to duplicate workspace",
      };
    }

    revalidatePath("/organization/workspaces");
    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function updateWorkspaceByUuidAction(
  workspaceUuid: string,
  data: {
    name?: string;
    description?: string;
  }
): Promise<ActionResult> {
  try {
    const result = await remotePut<ApiResponse>(`/workspaces/${workspaceUuid}`, data);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to update workspace",
      };
    }

    revalidatePath("/organization/workspaces");
    revalidatePath("/organization");
    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function getWorkspaceEmbedCodeAction(): Promise<ActionResult> {
  try {
    const result = await remoteGet<ApiResponse>("/workspace/embed-code");

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch workspace embed code",
      };
    }

    return {
      success: true,
      data: result.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}