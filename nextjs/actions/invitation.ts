"use server";

import { remoteGet, remotePost, remoteGetPublic, remoteDelete } from "@/lib/request";
import { ActionResult, CreateInvitationData, ApiResponse } from "@/interfaces";
import { revalidatePath } from "next/cache";


export async function getReceivedInvitationsAction(): Promise<ActionResult> {
  try {
    const result = await remoteGet<ApiResponse<any>>("/invitations/received");

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch received invitations",
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

export async function createInvitationAction(data: CreateInvitationData): Promise<ActionResult> {
  try {
    const result = await remotePost<ApiResponse<any>>("/invitations", data);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to create invitation",
      };
    }

    revalidatePath("/invitations");
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

// Public endpoint for invitation preview (no auth required)
export async function getInvitationPreviewAction(token: string): Promise<ActionResult> {
  try {
    const result = await remoteGetPublic<ApiResponse<any>>(`/invitations/${token}/preview`);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch invitation preview",
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

// Check invitation status (public endpoint)
export async function checkInvitationStatusAction(token: string): Promise<ActionResult> {
  try {
    const result = await remoteGetPublic<ApiResponse<any>>(`/invitations/${token}/check-status`);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to check invitation status",
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

// Accept invitation by token (auth required)
export async function acceptInvitationByTokenAction(token: string): Promise<ActionResult> {
  try {
    const result = await remotePost<ApiResponse<any>>(`/invitations/${token}/accept`, {});

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to accept invitation",
      };
    }

    revalidatePath("/invitations");
    return { success: true };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

// Decline invitation by token (auth required)
export async function declineInvitationByTokenAction(token: string): Promise<ActionResult> {
  try {
    const result = await remotePost<ApiResponse<any>>(`/invitations/${token}/decline`, {});

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to decline invitation",
      };
    }

    revalidatePath("/invitations");
    return { success: true };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}


// Bulk invite users
export async function bulkInviteUsersAction(
  invitations: Array<{
    email: string;
    role: string;
  }>,
  message?: string
): Promise<ActionResult> {
  try {
    const requestData = {
      invitations,
      ...(message && { message })
    };

    const result = await remotePost<ApiResponse<any>>("/invitations/bulk", requestData);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to send bulk invitations",
      };
    }

    revalidatePath("/invitations");
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

// Resend invitation
export async function resendInvitationAction(invitationId: string): Promise<ActionResult> {
  try {
    const result = await remotePost<ApiResponse<any>>(`/invitations/${invitationId}/resend`, {});

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to resend invitation",
      };
    }

    revalidatePath("/invitations");
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

// Delete invitation
export async function deleteInvitationAction(invitationUuid: string): Promise<ActionResult> {
  try {
    const result = await remoteDelete<ApiResponse<any>>(`/invitations/${invitationUuid}`);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to delete invitation",
      };
    }

    revalidatePath("/invitations");
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

// Organization invitation with workspace assignments
export async function createOrganizationInvitationAction(
  data: {
    email: string;
    role: "admin" | "member";
    workspace_assignments?: Array<{
      workspace_id: string;
      role: "manager" | "viewer";
    }>;
  }
): Promise<ActionResult> {
  try {
    const result = await remotePost<ApiResponse<any>>(
      `/invitations`,
      data
    );

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to send invitation",
      };
    }

    revalidatePath("/invitations");
    revalidatePath("/workspace");
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