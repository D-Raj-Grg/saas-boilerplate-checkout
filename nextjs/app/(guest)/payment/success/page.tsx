"use client";

import { useEffect, useState, useRef } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { verifyPaymentAction } from "@/actions/payment";
import { setAuthTokenAction } from "@/actions/auth";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { CheckCircle, Loader2, XCircle } from "lucide-react";
import Link from "next/link";

export default function PaymentSuccessPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [status, setStatus] = useState<"verifying" | "success" | "failed">("verifying");
  const [errorMessage, setErrorMessage] = useState<string>("");

  // Prevent double execution due to React re-renders
  const hasVerified = useRef(false);

  useEffect(() => {
    // Skip if already verified (prevents double API calls)
    if (hasVerified.current) {
      return;
    }

    const verifyPayment = async () => {
      // Try to get payment_uuid from URL params first (Khalti)
      let paymentUuid = searchParams.get("payment_uuid");

      // If not found, try to extract from eSewa's base64 data parameter
      if (!paymentUuid) {
        const esewaData = searchParams.get("data");
        if (esewaData) {
          try {
            const decoded = JSON.parse(atob(esewaData));
            paymentUuid = decoded.transaction_uuid;
          } catch (e) {
            console.error("Failed to decode eSewa data", e);
          }
        }
      }

      if (!paymentUuid) {
        setStatus("failed");
        setErrorMessage("Payment information not found");
        return;
      }

      // Mark as verified before making the call
      hasVerified.current = true;

      // Collect all verification parameters
      const verificationParams: Record<string, string> = {};
      searchParams.forEach((value, key) => {
        if (key !== "payment_uuid") {
          verificationParams[key] = value;
        }
      });

      try {
        const result = await verifyPaymentAction(paymentUuid, verificationParams);

        if (result.success && result.planAttached) {
          // Auto-login for guest checkouts
          if (result.isGuestCheckout && result.accessToken) {
            await setAuthTokenAction(result.accessToken);
            console.log("Guest user auto-logged in");
          }

          setStatus("success");
          // Redirect to dashboard after 3 seconds
          setTimeout(() => {
            router.push("/dashboard");
          }, 3000);
        } else {
          setStatus("failed");
          setErrorMessage(result.error || "Payment verification failed");
        }
      } catch (error) {
        console.log(error);
        setStatus("failed");
        setErrorMessage("An unexpected error occurred");
      }
    };

    verifyPayment();
  }, [searchParams]);

  return (
    <div className="container max-w-2xl mx-auto py-16 px-4">
      <Card>
        <CardHeader>
          <CardTitle className="text-center">
            {status === "verifying" && "Verifying Payment..."}
            {status === "success" && "Payment Successful!"}
            {status === "failed" && "Payment Failed"}
          </CardTitle>
        </CardHeader>
        <CardContent className="text-center space-y-6">
          {status === "verifying" && (
            <>
              <Loader2 className="w-16 h-16 mx-auto animate-spin text-primary" />
              <p className="text-muted-foreground">
                Please wait while we verify your payment...
              </p>
            </>
          )}

          {status === "success" && (
            <>
              <CheckCircle className="w-16 h-16 mx-auto text-green-500" />
              <div>
                <p className="text-lg font-medium mb-2">
                  Your payment has been verified successfully!
                </p>
                <p className="text-muted-foreground">
                  Your subscription is now active. Redirecting to dashboard...
                </p>
              </div>
              <Button asChild>
                <Link href="/dashboard">Go to Dashboard</Link>
              </Button>
            </>
          )}

          {status === "failed" && (
            <>
              <XCircle className="w-16 h-16 mx-auto text-red-500" />
              <div>
                <p className="text-lg font-medium mb-2">
                  Payment verification failed
                </p>
                <p className="text-muted-foreground">{errorMessage}</p>
              </div>
              <div className="flex gap-4 justify-center">
                <Button asChild variant="outline">
                  <Link href="/pricing">Back to Pricing</Link>
                </Button>
                <Button asChild>
                  <Link href="/dashboard">Go to Dashboard</Link>
                </Button>
              </div>
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
