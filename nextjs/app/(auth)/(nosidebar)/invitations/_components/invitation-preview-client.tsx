"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Building, User, Clock, Mail, ShieldAlert } from "lucide-react";
import { getInvitationPreviewAction, acceptInvitationByTokenAction, declineInvitationByTokenAction } from "@/actions/invitation";
import { useUserStore } from "@/stores/user-store";
import { loadUserData } from "@/lib/auth-client";
import { logoutAction } from "@/actions/auth";
import { toast } from "sonner";

interface InvitationPreviewData {
  invitation: {
    email: string;
    role: string;
    message?: string;
    expires_at: string;
  };
  workspace: {
    name: string;
    slug: string;
  };
  organization: {
    name: string;
    slug: string;
  };
  inviter: {
    name: string;
  };
  requires_signup: boolean;
}

interface InvitationPreviewClientProps {
  token: string;
}

export function InvitationPreviewClient({ token }: InvitationPreviewClientProps) {
  const [invitationData, setInvitationData] = useState<InvitationPreviewData | null>(null);
  const [loading, setLoading] = useState(true);
  const [accepting, setAccepting] = useState(false);
  const [declining, setDeclining] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();
  const { userData } = useUserStore();

  useEffect(() => {
    // Load user data first to ensure we have current user info
    loadUserData().then(() => {
      loadInvitationPreview();
    });
  }, [token]); // eslint-disable-line react-hooks/exhaustive-deps

  async function loadInvitationPreview() {
    try {
      const result = await getInvitationPreviewAction(token);

      if (result.success) {
        setInvitationData(result.data);
      } else {
        setError(result.error || "Failed to load invitation");
      }
    } catch {
      setError("An unexpected error occurred");
    } finally {
      setLoading(false);
    }
  }

  async function handleAcceptInvitation() {
    if (!userData) {
      // User not logged in, redirect to login with invitation token
      router.push(`/login?invitation=${token}`);
      return;
    }

    setAccepting(true);
    try {
      const result = await acceptInvitationByTokenAction(token);

      if (result.success) {
        toast.success("Invitation accepted successfully!");
        await loadUserData(); // Refresh user data to include new workspace
        router.push("/dashboard");
      } else {
        toast.error(result.error || "Failed to accept invitation");
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setAccepting(false);
    }
  }

  function handleSignUp() {
    router.push(`/join-waitlist?invitation=${token}&email=${encodeURIComponent(invitationData?.invitation.email || "")}`);
  }

  function handleLogin() {
    router.push(`/login?invitation=${token}`);
  }

  async function handleDeclineInvitation() {
    if (!userData) {
      toast.error("Please log in to decline this invitation");
      return;
    }

    setDeclining(true);
    try {
      const result = await declineInvitationByTokenAction(token);

      if (result.success) {
        toast.success("Invitation declined successfully");
        router.push("/dashboard");
      } else {
        toast.error(result.error || "Failed to decline invitation");
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setDeclining(false);
    }
  }

  if (loading) {
    return (
      <Card className="shadow-none">
        <CardContent className="flex items-center justify-center py-16">
          <div className="text-center">
            <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-primary mx-auto"></div>
            <p className="mt-4 text-muted-foreground font-medium">Loading invitation...</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (error) {
    return (
      <Card className="shadow-none">
        <CardContent className="flex items-center justify-center py-16">
          <div className="text-center max-w-sm">
            <div className="bg-red-50 p-4 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
              <ShieldAlert className="h-10 w-10 text-red-600" />
            </div>
            <h3 className="text-2xl font-bold mb-3 text-foreground">Invalid Invitation</h3>
            <p className="text-muted-foreground mb-6 leading-relaxed">{error}</p>
            <Button
              onClick={() => router.push("/login")}
              variant="outline"
              className="w-full"
              size="lg"
            >
              Go to Login
            </Button>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (!invitationData) {
    return null;
  }

  const isExpired = new Date(invitationData.invitation.expires_at) < new Date();
  const currentUserEmail = userData?.user?.email;
  const invitationEmail = invitationData.invitation.email;

  // Check if logged-in user's email matches the invitation email
  const isEmailMismatch = currentUserEmail &&
    currentUserEmail.toLowerCase() !== invitationEmail.toLowerCase();

  // Show email mismatch error if user is logged in with different email
  if (isEmailMismatch) {
    return (
      <Card className="shadow-none">
        <CardContent className="flex items-center justify-center py-16">
          <div className="text-center max-w-sm">
            <div className="bg-amber-50 p-4 rounded-full w-20 h-20 mx-auto mb-6 flex items-center justify-center">
              <ShieldAlert className="h-10 w-10 text-amber-600" />
            </div>
            <h3 className="text-2xl font-bold mb-3 text-foreground">Wrong Account</h3>
            <p className="text-muted-foreground mb-6 leading-relaxed">
              This invitation was sent to <strong className="text-foreground">{invitationEmail}</strong>,
              but you&apos;re currently logged in as <strong className="text-foreground">{currentUserEmail}</strong>.
            </p>
            <p className="text-muted-foreground mb-8">
              To accept this invitation, please log out and sign in with the correct email address.
            </p>
            <div className="space-y-3">
              <Button
                onClick={async () => {
                  // Log out first, then redirect to login with invitation
                  await logoutAction();
                  router.push(`/login?redirect_url=/invitations/${token}`);
                }}
                className="w-full"
                size="lg"
              >
                Switch Account
              </Button>
              <Button
                onClick={() => router.push("/dashboard")}
                variant="outline"
                className="w-full"
                size="lg"
              >
                Go to Dashboard
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    );
  }


  return (
    <Card className="w-full max-w-md mx-auto shadow-none">
      {/* Header */}
      <CardHeader className="text-center pb-4">
        <div className="flex justify-center mb-4">
          <div className="bg-primary/10 p-3 rounded-full">
            <Mail className="h-8 w-8 text-primary" />
          </div>
        </div>
        <CardTitle className="text-2xl mb-2">You&apos;re Invited!</CardTitle>
        <CardDescription className="text-base">
          <strong className="text-foreground">{invitationData.inviter.name}</strong> has invited you to join their organization
        </CardDescription>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Workspace Info - Compact */}
        <div className="space-y-3">
          <div className="flex items-center gap-3">
            <div className="bg-primary/10 p-2 rounded-md">
              <Building className="h-4 w-4 text-primary" />
            </div>
            <div className="min-w-0">
              <p className="font-medium text-sm truncate">{invitationData.organization.name}</p>
              <p className="text-xs text-muted-foreground">Organization</p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <div className="bg-purple-100 p-2 rounded-md">
              <User className="h-4 w-4 text-purple-600" />
            </div>
            <div className="flex items-center gap-2">
              <span className="text-sm font-medium">Role:</span>
              <Badge variant="secondary" className="text-xs">
                {invitationData.invitation.role}
              </Badge>
            </div>
          </div>
        </div>

        {/* Message - Only if exists and compact */}
        {invitationData.invitation.message && (
          <div className="bg-muted/50 p-3 rounded-md">
            <p className="text-xs text-muted-foreground mb-1">Message</p>
            <p className="text-sm italic">&ldquo;{invitationData.invitation.message}&rdquo;</p>
          </div>
        )}

        {/* Expiry - Compact */}
        <div className="flex items-center justify-center gap-2 text-center py-2">
          <Clock className="h-3 w-3 text-muted-foreground" />
          <span className={`text-xs ${isExpired ? 'text-destructive' : 'text-muted-foreground'}`}>
            {isExpired
              ? "Invitation expired"
              : `Expires ${new Date(invitationData.invitation.expires_at).toLocaleDateString()}`
            }
          </span>
        </div>

        {/* Actions - Prominent and at top of card content */}
        {!isExpired && (
          <div className="pt-2 border-t">
            {invitationData.requires_signup ? (
              <div className="space-y-3">
                <p className="text-center text-sm text-muted-foreground">
                  Create an account to accept this invitation
                </p>
                <div className="space-y-2">
                  <Button onClick={handleSignUp} className="w-full" size="sm">
                    Create Account & Accept
                  </Button>
                  <Button onClick={handleLogin} variant="outline" className="w-full" size="sm">
                    I have an account
                  </Button>
                </div>
              </div>
            ) : userData ? (
              <div className="space-y-3">
                <p className="text-center text-xs text-muted-foreground">
                  Logged in as <strong className="text-foreground">{currentUserEmail?.split('@')[0] || "..."}</strong>
                </p>
                <div className="space-y-2">
                  <Button
                    onClick={handleAcceptInvitation}
                    disabled={accepting || declining}
                    className="w-full"
                    size="sm"
                  >
                    {accepting ? (
                      <div className="flex items-center gap-2">
                        <div className="animate-spin rounded-full h-3 w-3 border-b-2 border-current border-t-transparent"></div>
                        Accepting...
                      </div>
                    ) : (
                      "Accept Invitation"
                    )}
                  </Button>
                  <Button
                    onClick={handleDeclineInvitation}
                    disabled={accepting || declining}
                    variant="outline"
                    className="w-full"
                    size="sm"
                  >
                    {declining ? "Declining..." : "Decline"}
                  </Button>
                </div>
              </div>
            ) : (
              <div className="space-y-3">
                <p className="text-center text-sm text-muted-foreground">
                  Please log in to accept this invitation
                </p>
                <Button onClick={handleLogin} className="w-full" size="sm">
                  Log In & Accept
                </Button>
              </div>
            )}
          </div>
        )}

        {isExpired && (
          <div className="pt-2 border-t text-center space-y-3">
            <div className="bg-destructive/10 p-3 rounded-md">
              <div className="flex justify-center mb-2">
                <Clock className="h-5 w-5 text-destructive" />
              </div>
              <p className="text-sm font-medium text-destructive mb-1">Invitation Expired</p>
              <p className="text-xs text-muted-foreground">
                Contact <strong>{invitationData.inviter.name}</strong> for a new invitation
              </p>
            </div>
            <Button onClick={() => router.push("/login")} variant="outline" size="sm">
              Go to Login
            </Button>
          </div>
        )}
      </CardContent>
    </Card>
  );
}