"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { resetPasswordAction, verifyPasswordTokenAction } from "@/actions/auth";
import { AuthBanner } from "@/components/auth/auth-banner";
import Image from "next/image";

const resetPasswordSchema = z.object({
  password: z.string().min(8, "Password must be at least 8 characters"),
  password_confirmation: z.string().min(8, "Password confirmation is required"),
}).refine((data) => data.password === data.password_confirmation, {
  message: "Passwords do not match",
  path: ["password_confirmation"],
});

type ResetPasswordFormValues = z.infer<typeof resetPasswordSchema>;

export function ResetPasswordForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [isLoading, setIsLoading] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isValidatingToken, setIsValidatingToken] = useState(true);

  const form = useForm<ResetPasswordFormValues>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      password: "",
      password_confirmation: "",
    },
  });

  useEffect(() => {
    // Extract token from URL parameters
    const urlToken = searchParams.get("token");
    if (urlToken) {
      setToken(urlToken);
      // Validate the token
      validateToken(urlToken);
    } else {
      setError("No reset token found. Please request a new password reset link.");
      setIsValidatingToken(false);
    }
  }, [searchParams]);

  async function validateToken(tokenValue: string) {
    try {
      const result = await verifyPasswordTokenAction({ token: tokenValue });
      if (result.success) {
        setIsValidatingToken(false);
      } else {
        setError(result.error || "Invalid or expired reset token");
        setIsValidatingToken(false);
      }
    } catch {
      setError("An error occurred while validating the token");
      setIsValidatingToken(false);
    }
  }

  async function onSubmit(data: ResetPasswordFormValues) {
    if (!token) {
      setError("No reset token available");
      return;
    }

    try {
      setIsLoading(true);
      setError(null);

      const result = await resetPasswordAction({
        token,
        password: data.password,
        password_confirmation: data.password_confirmation,
      });

      if (result.success) {
        setIsSuccess(true);
        toast.success("Password reset successful!", {
          description: "Your password has been updated successfully.",
        });
        // Redirect to login after 3 seconds
        setTimeout(() => {
          router.push("/login");
        }, 3000);
      } else {
        setError(result.error || "Failed to reset password");
      }
    } catch {
      setError("An unexpected error occurred. Please try again.");
    } finally {
      setIsLoading(false);
    }
  }

  if (isSuccess) {
    return (
      <div className="flex h-screen">
        {/* Left side - Success Message */}
        <div className="flex w-full min-h-screen items-center justify-center bg-background lg:w-1/2 px-4 py-8">
          <div className="w-full max-w-sm space-y-6">
            {/* Mobile Logo - Shows only on small screens */}
            <div className="flex justify-center lg:hidden">
              <Image
                src="/logo-full.svg"
                alt={process.env.NEXT_PUBLIC_APP_NAME || "Logo"}
                width={140}
                height={40}
                priority
              />
            </div>

            <Card className="w-full border-0">
            <CardHeader className="space-y-1 text-center lg:text-left">
              <div className="mx-auto lg:mx-0 mb-4 h-16 w-16 rounded-full bg-success flex items-center justify-center">
                <svg className="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <CardTitle className="text-3xl font-bold">Password Reset Successful!</CardTitle>
              <CardDescription className="text-base">
                Your password has been updated successfully.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground text-center mb-4">
                Redirecting you to login page...
              </p>
              <div className="flex justify-center">
                <svg className="animate-spin h-6 w-6 text-foreground" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
              </div>
            </CardContent>
            </Card>
          </div>
        </div>

        {/* Right side - Modern Animated Banner */}
        <AuthBanner
          heading="Welcome
Back"
          description="Your password has been successfully updated. You can now sign in with your new password and continue using our platform."
          badges={[
            { value: "✓", label: "Password Updated" },
            { value: "Secure", label: "Account Access" },
            { value: "Ready", label: "To Continue" },
          ]}
        />
      </div>
    );
  }

  // Show loading state while validating token
  if (isValidatingToken) {
    return (
      <div className="flex h-screen">
        {/* Left side - Validation Loading */}
        <div className="flex w-full min-h-screen items-center justify-center bg-background lg:w-1/2 px-4 py-8">
          <div className="w-full max-w-sm space-y-6">
            {/* Mobile Logo - Shows only on small screens */}
            <div className="flex justify-center lg:hidden">
              <Image
                src="/logo-full.svg"
                alt={process.env.NEXT_PUBLIC_APP_NAME || "Logo"}
                width={140}
                height={40}
                priority
              />
            </div>

            <Card className="w-full border-0">
            <CardHeader className="space-y-1 text-center lg:text-left">
              <CardTitle className="text-3xl font-bold text-foreground">
                Validating Reset Link
              </CardTitle>
              <CardDescription className="text-base">
                Please wait while we validate your reset link...
              </CardDescription>
            </CardHeader>
            <CardContent className="flex justify-center py-8">
              <svg className="animate-spin h-8 w-8 text-foreground" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
            </CardContent>
            </Card>
          </div>
        </div>

        {/* Right side - Modern Animated Banner */}
        <AuthBanner
          heading="Almost
There"
          description="We're validating your password reset link to ensure it's secure and hasn't expired. This will only take a moment."
          badges={[
            { value: "Secure", label: "Link Validation" },
            { value: "Safe", label: "Reset Process" },
            { value: "Quick", label: "Verification" },
          ]}
        />
      </div>
    );
  }

  // Show error state if token validation failed
  if (error) {
    return (
      <div className="flex h-screen">
        {/* Left side - Error State */}
        <div className="flex w-full min-h-screen items-center justify-center bg-background lg:w-1/2 px-4 py-8">
          <div className="w-full max-w-sm space-y-6">
            {/* Mobile Logo - Shows only on small screens */}
            <div className="flex justify-center lg:hidden">
              <Image
                src="/logo-full.svg"
                alt={process.env.NEXT_PUBLIC_APP_NAME || "Logo"}
                width={140}
                height={40}
                priority
              />
            </div>

            <Card className="w-full border-0">
            <CardHeader className="space-y-1 text-center lg:text-left">
              <CardTitle className="text-3xl font-bold text-foreground">
                Invalid Reset Link
              </CardTitle>
              <CardDescription className="text-base">
                The password reset link is invalid or has expired.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="rounded-md p-3 text-sm text-destructive border border-destructive/20 mb-4">
                {error}
              </div>
              <Button
                onClick={() => router.push("/forgot-password")}
                className="w-full h-11"
              >
                Request New Reset Link
              </Button>
            </CardContent>
            <CardFooter>
              <div className="text-center text-sm text-muted-foreground w-full">
                Remember your password?{" "}
                <Link href="/login" className="font-semibold text-foreground hover:text-muted-foreground transition-colors">
                  Back to login
                </Link>
              </div>
            </CardFooter>
            </Card>
          </div>
        </div>

        {/* Right side - Modern Animated Banner */}
        <AuthBanner
          heading="Need a New
Link?"
          description="No worries! Password reset links expire for security. Request a new one and we'll send it to your email address right away."
          badges={[
            { value: "Fresh", label: "Reset Link" },
            { value: "Secure", label: "Process" },
            { value: "24/7", label: "Support" },
          ]}
        />
      </div>
    );
  }

  return (
    <div className="flex h-screen">
      {/* Left side - Reset Password Form */}
      <div className="flex w-full min-h-screen items-center justify-center bg-background lg:w-1/2 px-4 py-8">
        <div className="w-full max-w-sm space-y-6">
          {/* Mobile Logo - Shows only on small screens */}
          <div className="flex justify-center lg:hidden">
            <Image
              src="/logo_with_text.svg"
              alt={process.env.NEXT_PUBLIC_APP_NAME || "Logo"}
              width={140}
              height={40}
              priority
            />
          </div>

          <Card className="w-full border-0">
          <CardHeader className="space-y-1 text-center lg:text-left">
            <CardTitle className="text-3xl font-bold text-foreground">
              Reset Your Password
            </CardTitle>
            <CardDescription className="text-base">
              Create a new password for your account
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Form {...form}>
              <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
                {error && (
                  <div className="rounded-md p-3 text-sm text-destructive border border-destructive/20">
                    {error}
                  </div>
                )}

                <FormField
                  control={form.control}
                  name="password"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-sm font-medium">New Password</FormLabel>
                      <FormControl>
                        <Input
                          type="password"
                          placeholder="••••••••"
                          className="h-11"
                          {...field}
                          disabled={isLoading}
                        />
                      </FormControl>
                      <FormMessage className="text-xs" />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="password_confirmation"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-sm font-medium">Confirm New Password</FormLabel>
                      <FormControl>
                        <Input
                          type="password"
                          placeholder="••••••••"
                          className="h-11"
                          {...field}
                          disabled={isLoading}
                        />
                      </FormControl>
                      <FormMessage className="text-xs" />
                    </FormItem>
                  )}
                />

                <Button
                  type="submit"
                  className="w-full h-11"
                  disabled={isLoading}
                >
                  {isLoading ? (
                    <span className="flex items-center gap-2">
                      <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                      </svg>
                      Resetting...
                    </span>
                  ) : (
                    "Reset Password"
                  )}
                </Button>
              </form>
            </Form>
          </CardContent>
          <CardFooter className="flex flex-col gap-2">
            <div className="text-center text-sm text-muted-foreground w-full">
              Didn&apos;t receive the link?{" "}
              <Link href="/forgot-password" className="font-semibold text-foreground hover:text-muted-foreground transition-colors">
                Request new link
              </Link>
            </div>
            <div className="text-center text-sm text-muted-foreground w-full">
              Remember your password?{" "}
              <Link href="/login" className="font-semibold text-foreground hover:text-muted-foreground transition-colors">
                Back to login
              </Link>
            </div>
          </CardFooter>
          </Card>
        </div>
      </div>

      {/* Right side - Modern Animated Banner */}
      <AuthBanner
        heading="New Password,
Fresh Start"
        description="Choose a strong password to secure your account. Make it unique and memorable so you can continue testing with confidence."
        badges={[
          { value: "Strong", label: "Security" },
          { value: "Safe", label: "Account" },
          { value: "Ready", label: "To Test" },
        ]}
      />
    </div>
  );
}