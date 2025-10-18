"use server";

import { cookies } from "next/headers";
import { getCookieOptions } from "@/lib/utils";
import { LoginResponse, SignupResponse, AuthActionResult, LoginData, SignupData, ForgotPasswordData, VerifyPasswordTokenData, ResetPasswordData, WaitlistData, WaitlistResponse } from "@/interfaces";
import { remotePost, remotePostPublic } from "@/lib/request";

const AUTH_TOKEN_NAME = process.env.AUTH_TOKEN_NAME || 'secure_cookie_name';

// Using centralized AuthActionResult interface

export async function loginAction(data: LoginData): Promise<AuthActionResult> {
  try {
    const result = await remotePostPublic<LoginResponse & {
      errors?: {
        email?: string[];
        password?: string[]
      }
    }>("/login", data);

    // If result is null or undefined, it means there was an error
    if (!result) {
      return {
        success: false,
        error: "Login failed",
      };
    }

    // Check if the response indicates success
    if (!result.success || !result.data?.access_token) {
      // Extract specific error message from errors object if available
      const emailError = result.errors?.email?.[0];
      const passwordError = result.errors?.password?.[0];
      const specificError = emailError || passwordError;

      return {
        success: false,
        error: specificError || result.message || "Login failed",
      };
    }

    // Set the auth token in a secure HTTP-only cookie
    (await cookies()).set(AUTH_TOKEN_NAME, result.data.access_token, getCookieOptions());

    return {
      success: true
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function signupAction(data: SignupData): Promise<AuthActionResult> {
  try {
    // Format data for API - using snake_case as required by the API
    const apiData = {
      first_name: data.firstName,
      last_name: data.lastName,
      name: `${data.firstName} ${data.lastName}`,
      email: data.email,
      password: data.password,
      ...(data.invitationToken && { invitation_token: data.invitationToken }),
    };

    const result = await remotePostPublic<SignupResponse & {
      errors?: {
        email?: string[];
        password?: string[];
        first_name?: string[];
        last_name?: string[];
      }
    }>("/register", apiData);

    // If result is null or undefined, it means there was an error
    if (!result) {
      return {
        success: false,
        error: "Failed to create account",
      };
    }

    // Check if the response indicates success
    if (!result.success || !result.data?.access_token) {
      // Extract specific error messages from errors object if available
      const emailError = result.errors?.email?.[0];
      const passwordError = result.errors?.password?.[0];
      const firstNameError = result.errors?.first_name?.[0];
      const lastNameError = result.errors?.last_name?.[0];
      const specificError = emailError || passwordError || firstNameError || lastNameError;

      return {
        success: false,
        error: specificError || result.message || "Registration failed",
      };
    }

    // Set the auth token in a secure HTTP-only cookie
    (await cookies()).set(AUTH_TOKEN_NAME, result.data.access_token, getCookieOptions());

    return {
      success: true
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function logoutAction(): Promise<void> {
  // Delete cookie with the same options for consistency
  (await cookies()).set(AUTH_TOKEN_NAME, "", getCookieOptions({ maxAge: 0 }));
}

/**
 * Set auth token (for guest checkout auto-login)
 */
export async function setAuthTokenAction(token: string): Promise<void> {
  (await cookies()).set(AUTH_TOKEN_NAME, token, getCookieOptions());
}

export async function forgotPasswordAction(data: ForgotPasswordData): Promise<AuthActionResult> {
  try {
    const result = await remotePostPublic<{ success: boolean; data?: any; message?: string }>("/password/forgot", data);

    // TODO: Handle rate limiting (429) in request utility
    if (!result) {
      return {
        success: false,
        error: "An unexpected error occurred",
      };
    }

    // API always returns 200 OK to prevent email enumeration
    return {
      success: true,
      data: result?.data
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function verifyPasswordTokenAction(data: VerifyPasswordTokenData): Promise<AuthActionResult> {
  try {
    const result = await remotePostPublic<{
      success: boolean;
      data?: any;
      message?: string;
      errors?: { token?: string[] };
    }>("/password/verify-token", data);

    if (!result || !result.success) {
      // Extract specific error message from errors.token array if available
      const tokenError = result?.errors?.token?.[0];
      return {
        success: false,
        error: tokenError || result?.message || "Invalid or expired token",
      };
    }
    return {
      success: true,
      data: result?.data
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function resetPasswordAction(data: ResetPasswordData): Promise<AuthActionResult> {
  try {
    const result = await remotePostPublic<{ success: boolean; message?: string }>("/password/reset", data);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to reset password",
      };
    }
    return { success: true };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function verifyEmailAction(token: string): Promise<AuthActionResult> {
  try {
    const result = await remotePostPublic<{ success: boolean; message?: string; data?: any }>("/email/verify", { token });

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to verify email",
      };
    }

    return {
      success: true,
      message: result.message || "Email verified successfully"
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function resendVerificationEmailAction(): Promise<AuthActionResult> {
  try {
    const result = await remotePost<{ success: boolean; message?: string }>("/email/resend", {});

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to resend verification email",
      };
    }

    return {
      success: true,
      message: result.message || "Verification email sent"
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function getEmailVerificationStatusAction(): Promise<AuthActionResult> {
  try {
    const result = await remotePost<{ success: boolean; data?: { email_verified: boolean; email_verified_at: string | null } }>("/email/verification-status", {});

    if (!result || !result.success) {
      return {
        success: false,
        error: "Failed to get verification status",
      };
    }

    return {
      success: true,
      data: result.data
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export async function joinWaitlistAction(data: WaitlistData): Promise<AuthActionResult> {
  try {
    const result = await remotePostPublic<WaitlistResponse>("/waitlist/join", data);

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to join waitlist",
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

export async function exchangeGoogleCodeAction(code: string): Promise<AuthActionResult> {
  try {
    const result = await remotePostPublic<LoginResponse & {
      errors?: {
        code?: string[];
      }
    }>("/auth/google/exchange", { code });

    // If result is null or undefined, it means there was an error
    if (!result) {
      return {
        success: false,
        error: "Token exchange failed",
      };
    }

    // Check if the response indicates success
    if (!result.success || !result.data?.access_token) {
      const codeError = result.errors?.code?.[0];
      return {
        success: false,
        error: codeError || result.message || "Google authentication failed",
      };
    }

    // Set the auth token in a secure HTTP-only cookie
    (await cookies()).set(AUTH_TOKEN_NAME, result.data.access_token, getCookieOptions());

    return {
      success: true
    };
  } catch {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}