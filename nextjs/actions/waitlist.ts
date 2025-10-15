"use server";

import { joinWaitlistAction as authJoinWaitlistAction } from "@/actions/auth";
import type { WaitlistData } from "@/interfaces";
import { ActionResult } from "@/interfaces";

export async function joinWaitlistAction(data: WaitlistData): Promise<ActionResult> {
  return await authJoinWaitlistAction(data);
}