"use client";

import { useState, useEffect } from "react";
import { useUserStore } from "@/stores/user-store";
import { getReceivedInvitationsAction } from "@/actions/invitation";

export function usePendingNotifications() {
  const { userData } = useUserStore();
  const user = userData?.user || null;
  const [hasPendingInvitations, setHasPendingInvitations] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    async function checkPendingNotifications() {
      if (!user) {
        setIsLoading(false);
        return;
      }

      try {
        const result = await getReceivedInvitationsAction();
        if (result.success && result.data) {
          // Filter non-expired invitations
          const validInvitations = result.data.filter((invitation: any) => 
            new Date(invitation.expires_at) > new Date()
          );
          setHasPendingInvitations(validInvitations.length > 0);
        }
      } catch {
      } finally {
        setIsLoading(false);
      }
    }

    checkPendingNotifications();
  }, [user]);

  const hasEmailVerification = user && !user.email_verified_at;
  const hasNotifications = hasEmailVerification || hasPendingInvitations;

  return {
    hasNotifications,
    hasEmailVerification,
    hasPendingInvitations,
    isLoading
  };
}