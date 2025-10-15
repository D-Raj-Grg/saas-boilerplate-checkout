"use client";

import { useState } from "react";
// import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Mail, X, Loader2 } from "lucide-react";
import { resendVerificationEmailAction } from "@/actions/auth";
import { toast } from "sonner";

interface EmailVerificationBannerProps {
  email?: string;
  onDismiss?: () => void;
}

export function EmailVerificationBanner({ email, onDismiss }: EmailVerificationBannerProps) {
  const [isLoading, setIsLoading] = useState(false);
  const [isDismissed, setIsDismissed] = useState(false);

  const handleResend = async () => {
    setIsLoading(true);
    try {
      const result = await resendVerificationEmailAction();

      if (result.success) {
        toast.success(result.message || "Verification email has been sent to your email address.");
      } else {
        toast.error(result.error || "Failed to resend verification email. Please try again.");
      }
    } catch {
      toast.error("An unexpected error occurred. Please try again.");
    } finally {
      setIsLoading(false);
    }
  };

  const handleDismiss = () => {
    setIsDismissed(true);
    onDismiss?.();
  };

  if (isDismissed) {
    return null;
  }

  return (
    <div className="bg-yellow-50 px-4 py-1 flex items-center justify-between gap-4">
      <div className="flex items-center gap-3 flex-1">
        <div className="p-1.5 bg-yellow-100 rounded-lg">
          <Mail className="h-4 w-4 text-yellow-700 flex-shrink-0" />
        </div>
        <span className="text-yellow-900 text-sm">
          Please verify your email address{email ? ` (${email})` : ""} to access all features.
        </span>
      </div>
      <div className="flex items-center gap-2">
        <Button
          onClick={handleResend}
          disabled={isLoading}
          size="sm"
          className="h-8 bg-[#FF5B04] hover:bg-[#E54F03] text-white font-semibold"
        >
          {isLoading ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Sending...
            </>
          ) : (
            "Resend Email"
          )}
        </Button>
        {onDismiss && (
          <Button
            onClick={handleDismiss}
            variant="ghost"
            size="sm"
            className="h-8 w-8 p-0 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-200/50 rounded-full"
          >
            <X className="h-4 w-4" />
          </Button>
        )}
      </div>
    </div>
  );
}