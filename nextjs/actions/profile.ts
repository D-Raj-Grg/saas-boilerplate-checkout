"use server";

import { remoteGet, remotePut } from "@/lib/request";

export interface UserProfile {
  id: number;
  name: string;
  first_name: string;
  last_name: string;
  email: string;
  created_at: string;
  uuid: string;
}

export interface ProfileResponse {
  success: boolean;
  data: UserProfile;
}

export async function getUserProfile() {
  try {
    const data = await remoteGet<ProfileResponse>("/user");
    return {
      success: true,
      data: data.data,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}

export interface UpdateProfileData {
  first_name?: string;
  last_name?: string;
  old_password?: string;
  new_password?: string;
  new_password_confirmation?: string;
}

export interface UpdateProfileResponse {
  success: boolean;
  data: {
    user: UserProfile;
    password_changed: boolean;
  };
  message: string;
}

export async function updateUserProfile(profileData: UpdateProfileData) {
  try {
    const data = await remotePut<UpdateProfileResponse>("/user", profileData);
    
    if (!data.success) {
      return {
        success: false,
        error: data.message || "Failed to update profile",
        errors: (data as any).errors || {},
      };
    }
    
    return {
      success: true,
      data: data.data,
      message: data.message,
      password_changed: data.data.password_changed,
    };
  } catch (error) {
    return {
      success: false,
      error: "An unexpected error occurred",
    };
  }
}