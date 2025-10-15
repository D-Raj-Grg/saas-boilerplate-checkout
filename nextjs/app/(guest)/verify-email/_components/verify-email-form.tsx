"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Loader2, CheckCircle2, XCircle, Mail } from "lucide-react";
import { verifyEmailAction } from "@/actions/auth";

export function VerifyEmailForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const token = searchParams.get("token");

  const [status, setStatus] = useState<"loading" | "success" | "error" | "no-token">("loading");
  const [message, setMessage] = useState("");

  useEffect(() => {
    const verifyEmail = async () => {
      if (!token) {
        setStatus("no-token");
        setMessage("No verification token provided");
        return;
      }

      try {
        const result = await verifyEmailAction(token);

        if (result.success) {
          setStatus("success");
          setMessage(result.message || "Email verified successfully!");

          // Redirect to dashboard after 3 seconds
          setTimeout(() => {
            router.push("/dashboard");
          }, 3000);
        } else {
          setStatus("error");
          setMessage(result.error || "Failed to verify email");
        }
      } catch {
        setStatus("error");
        setMessage("An unexpected error occurred");
      }
    };

    verifyEmail();
  }, [token, router]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-background px-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <CardTitle className="text-3xl font-bold">Email Verification</CardTitle>
          <CardDescription>
            Verifying your email address
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          {status === "loading" && (
            <div className="flex flex-col items-center space-y-4">
              <Loader2 className="h-12 w-12 animate-spin text-primary" />
              <p className="text-sm text-gray-600">Verifying your email...</p>
            </div>
          )}

          {status === "success" && (
            <div className="flex flex-col items-center space-y-4">
              <CheckCircle2 className="h-12 w-12 text-green-600" />
              <Alert className="border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950">
                <AlertDescription className="text-green-800 dark:text-green-200">
                  {message}
                </AlertDescription>
              </Alert>
              <p className="text-sm text-muted-foreground">Redirecting to dashboard...</p>
            </div>
          )}

          {status === "error" && (
            <div className="flex flex-col items-center space-y-4">
              <XCircle className="h-12 w-12 text-red-600" />
              <Alert className="border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950">
                <AlertDescription className="text-red-800 dark:text-red-200">
                  {message}
                </AlertDescription>
              </Alert>
              <div className="flex flex-col gap-3 w-full">
                <Button
                  onClick={() => router.push("/dashboard")}
                  className="w-full"
                >
                  Go to Dashboard
                </Button>
                {message.includes("expired") && (
                  <p className="text-sm text-center text-gray-700">
                    You can request a new verification email from your dashboard
                  </p>
                )}
              </div>
            </div>
          )}

          {status === "no-token" && (
            <div className="flex flex-col items-center space-y-4">
              <Mail className="h-12 w-12 text-gray-600" />
              <Alert className="border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <AlertDescription className="text-gray-800 dark:text-gray-200">
                  {message}
                </AlertDescription>
              </Alert>
              <Button
                onClick={() => router.push("/dashboard")}
                className="w-full"
              >
                Go to Dashboard
              </Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}