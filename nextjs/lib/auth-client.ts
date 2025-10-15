"use client";

import { getMeAction } from "@/actions/user";
import { useUserStore } from "@/stores/user-store";

export async function loadUserData() {
  const result = await getMeAction();
  
  if (result.success && result.data) {
    useUserStore.getState().setUserData(result.data);
    return true;
  }
  
  return false;
}