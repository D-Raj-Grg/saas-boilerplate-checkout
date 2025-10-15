"use client";

import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Mail, Loader2 } from "lucide-react";
import { getEmailVerificationStatusAction, resendVerificationEmailAction } from "@/actions/auth";
import { toast } from "sonner";
import { useUserStore } from "@/stores/user-store";

export function EmailVerificationNotification() {
  const { userData } = useUserStore();
  const user = userData?.user || null;
  const [isVerified, setIsVerified] = useState<boolean | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isResending, setIsResending] = useState(false);

  useEffect(() => {
    async function checkVerificationStatus() {
      if (!user) {
        setIsLoading(false);
        return;
      }

      try {
        const result = await getEmailVerificationStatusAction();
        if (result.success && result.data) {
          setIsVerified(result.data.email_verified);
        } else {
          // Fallback to user data if API call fails
          setIsVerified(!!user.email_verified_at);
        }
      } catch {
        // Fallback to user data if API call fails
        setIsVerified(!!user.email_verified_at);
      } finally {
        setIsLoading(false);
      }
    }

    checkVerificationStatus();
  }, [user]);

  const handleResend = async () => {
    setIsResending(true);
    try {
      const result = await resendVerificationEmailAction();
      if (result.success) {
        toast.success(result.message || "Verification email sent!");
      } else {
        toast.error(result.error || "Failed to send email");
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setIsResending(false);
    }
  };

  // Show loading state
  if (isLoading) {
    return (
      <div className="p-3">
        <div className="flex items-center gap-3 text-sm text-muted-foreground">
          <Loader2 className="h-4 w-4 animate-spin" />
          Checking verification status...
        </div>
      </div>
    );
  }

  // Return empty if verified or no user
  if (!user || isVerified) {
    return null;
  }

  // Show verification notice if not verified
  return (
    <div className="p-3">
      <div className="bg-amber-50 border border-amber-200 rounded-lg p-3">
        <div className="flex items-start gap-3">
          <Mail className="h-4 w-4 text-amber-600 flex-shrink-0 mt-0.5" />
          <div className="flex-1">
            <h4 className="text-sm font-medium text-amber-800 mb-1">
              Email Verification Required
            </h4>
            <p className="text-xs text-amber-700 mb-2">
              Please verify your email address ({user.email}) to access all features.
            </p>
            <Button
              onClick={handleResend}
              disabled={isResending}
              size="sm"
              className="h-7 px-3 text-xs"
            >
              {isResending ? (
                <>
                  <Loader2 className="mr-2 h-3 w-3 animate-spin" />
                  Sending...
                </>
              ) : (
                "Resend Email"
              )}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}