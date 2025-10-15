"use client";

import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Loader2, Building, Users } from "lucide-react";
import { getReceivedInvitationsAction } from "@/actions/invitation";
import { useUserStore } from "@/stores/user-store";
import Link from "next/link";

interface ReceivedInvitation {
  token: string;
  role: string;
  message?: string;
  expires_at: string;
  organization: {
    uuid: string;
    name: string;
  },
  inviter: {
    name: string;
    email: string;
  };
  created_at: string;
}

export function ReceivedInvitationsNotification() {
  const { userData } = useUserStore();
  const user = userData?.user || null;
  const [invitations, setInvitations] = useState<ReceivedInvitation[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    async function fetchReceivedInvitations() {
      if (!user) {
        setIsLoading(false);
        return;
      }

      try {
        const result = await getReceivedInvitationsAction();
        if (result.success && result.data) {
          setInvitations(result.data);
        }
      } catch {
      } finally {
        setIsLoading(false);
      }
    }

    fetchReceivedInvitations();
  }, [user]);

  // Show loading state
  if (isLoading) {
    return (
      <div className="p-3">
        <div className="flex items-center gap-3 text-sm text-muted-foreground">
          <Loader2 className="h-4 w-4 animate-spin" />
          Checking invitations...
        </div>
      </div>
    );
  }

  // Return empty if no user or no invitations
  if (!user || !invitations.length) {
    return null;
  }

  // Filter non-expired invitations
  const validInvitations = invitations.filter(
    (invitation) => new Date(invitation.expires_at) > new Date()
  );

  if (validInvitations.length === 0) {
    return null;
  }

  return (
    <div className="mb-1">
      <div className="px-3">
        <h4 className="text-sm font-medium text-foreground mt-2 mb-1">
          Pending Invitations ({validInvitations.length})
        </h4>
      </div>
      {validInvitations.slice(0, 3).map((invitation) => (
        <div key={invitation.token} className="px-3 py-2">
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 hover:bg-blue-100 transition-colors">
            <Link href={`/invitations/${invitation.token}`} className="block">
              <div className="flex items-start gap-3">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-1">
                    <Building className="h-3 w-3 text-blue-600 flex-shrink-0" />
                    <p className="text-sm font-medium text-blue-900 truncate">
                      {invitation.organization.name}
                    </p>
                  </div>
                  <div className="flex items-center gap-2 mb-1">
                    <Users className="h-3 w-3 text-blue-600 flex-shrink-0" />
                    <p className="text-xs text-blue-700 truncate">
                      {invitation.organization.name} • {invitation.role}
                    </p>
                  </div>
                  <p className="text-xs text-blue-600">
                    From {invitation.inviter.name}
                  </p>
                </div>
              </div>
            </Link>
          </div>
        </div>
      ))}
      {validInvitations.length > 3 && (
        <div className="px-3 py-2">
          <Button
            variant="ghost"
            size="sm"
            className="w-full text-xs text-muted-foreground hover:text-foreground"
          >
            View all {validInvitations.length} invitations
          </Button>
        </div>
      )}
    </div>
  );
}