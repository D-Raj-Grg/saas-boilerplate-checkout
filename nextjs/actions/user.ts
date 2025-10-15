"use server";

import { remoteGet } from "@/lib/request";
import { ActionResult } from "@/interfaces";
import { cookies } from "next/headers";

const AUTH_TOKEN_NAME = process.env.AUTH_TOKEN_NAME || 'secure_cookie_name';

export async function checkAuthStatus(): Promise<boolean> {
  const cookieStore = await cookies();
  const authToken = cookieStore.get(AUTH_TOKEN_NAME);
  return !!authToken?.value;
}

export async function getMeAction(): Promise<ActionResult> {
  try {
    const result = await remoteGet<{ success: boolean; data?: any; message?: string }>("/me");

    if (!result || !result.success) {
      return {
        success: false,
        error: result?.message || "Failed to fetch user data",
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
