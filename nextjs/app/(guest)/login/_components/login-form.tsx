"use client";

import { useState, useEffect, useCallback } from "react";
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
import { loginAction, exchangeGoogleCodeAction } from "@/actions/auth";
import { acceptInvitationByTokenAction } from "@/actions/invitation";
import { setRedirectUrl, getAndClearRedirectUrl } from "@/lib/redirect-cookie";
import { AuthBanner } from "@/components/auth/auth-banner";
import Image from "next/image";

const loginSchema = z.object({
  email: z.string().email("Please enter a valid email address"),
  password: z.string().min(6, "Password must be at least 6 characters"),
});

type LoginFormValues = z.infer<typeof loginSchema>;

export function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [isLoading, setIsLoading] = useState(false);
  const [isGoogleLoading, setIsGoogleLoading] = useState(false);
  const [invitationToken, setInvitationToken] = useState<string | null>(null);

  const form = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: "",
      password: "",
    },
  });

  const handleOAuthCallback = useCallback(async (code: string) => {
    try {
      setIsGoogleLoading(true);

      const result = await exchangeGoogleCodeAction(code);

      if (result.success) {
        toast.success("Welcome back!", {
          description: "You have successfully logged in with Google.",
        });

        // Check for redirect URL and use it, otherwise default to dashboard
        const redirectUrl = getAndClearRedirectUrl();
        router.push(redirectUrl || "/dashboard");
      } else {
        const errorMessage = result.error || "Google authentication failed. Please try again.";
        toast.error("Google login failed", {
          description: errorMessage,
        });
        setIsGoogleLoading(false);
      }
    } catch {
      const errorMessage = "An unexpected error occurred. Please try again.";
      toast.error("Error", {
        description: errorMessage,
      });
      setIsGoogleLoading(false);
    }
  }, [router]);

  useEffect(() => {
    const invitation = searchParams.get("invitation");
    if (invitation) {
      setInvitationToken(invitation);
    }

    // Store redirect URL in cookie if present
    const redirectUrl = searchParams.get("redirect_url") ?? '/dashboard';
    if (redirectUrl) {
      setRedirectUrl(redirectUrl);
    }

    // Handle OAuth callback
    const code = searchParams.get("code");
    const error = searchParams.get("error");

    if (error) {
      toast.error("Google login failed", {
        description: "Please try again.",
      });
      return;
    }

    if (code) {
      handleOAuthCallback(code);
    }
  }, [searchParams, handleOAuthCallback]);

  async function onSubmit(data: LoginFormValues) {
    try {
      setIsLoading(true);

      const result = await loginAction(data);

      if (result.success) {
        toast.success("Welcome back!", {
          description: "You have successfully logged in.",
        });

        // Handle invitation acceptance after login
        if (invitationToken) {
          try {
            const inviteResult = await acceptInvitationByTokenAction(invitationToken);
            if (inviteResult.success) {
              toast.success("Invitation accepted successfully!");
            } else {
              toast.error("Login successful, but failed to accept invitation: " + inviteResult.error);
            }
          } catch {
            toast.error("Login successful, but failed to accept invitation");
          }
        }

        // Check for redirect URL and use it, otherwise default to dashboard
        const redirectUrl = getAndClearRedirectUrl();
        router.push(redirectUrl || "/dashboard");
      } else {
        const errorMessage = result.error || "Something went wrong, try again!";
        toast.error("Login failed", {
          description: errorMessage,
        });
      }
    } catch {
      const errorMessage = "An unexpected error occurred. Please try again.";
      toast.error("Error", {
        description: errorMessage,
      });
    } finally {
      setIsLoading(false);
    }
  }

  function handleGoogleLogin() {
    // Redirect to backend OAuth endpoint
    const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL;
    window.location.href = `${apiBaseUrl}/auth/google/redirect`;
  }

  return (
    <div className="flex h-screen">
      {/* Left side - Login Form */}
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
                Welcome Back
              </CardTitle>
              <CardDescription className="text-base">
                Sign in to access your {process.env.NEXT_PUBLIC_APP_NAME} dashboard
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">


                  {/* Google Login Button */}
                  <Button
                    type="button"
                    variant="outline"
                    className="w-full h-11 bg-white hover:bg-gray-50 border-gray-300 text-gray-700 font-medium shadow-sm transition-all"
                    onClick={handleGoogleLogin}
                    disabled={isLoading || isGoogleLoading}
                  >
                    {isGoogleLoading ? (
                      <span className="flex items-center gap-2">
                        <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        Signing in with Google...
                      </span>
                    ) : (
                      <>
                        <svg className="mr-2 h-5 w-5" viewBox="0 0 24 24">
                          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                        </svg>
                        Continue with Google
                      </>
                    )}
                  </Button>

                  {/* Divider */}
                  <div className="relative">
                    <div className="absolute inset-0 flex items-center">
                      <span className="w-full border-t border-border" />
                    </div>
                    <div className="relative flex justify-center text-xs uppercase">
                      <span className="bg-card px-2 text-muted-foreground">Or continue with email</span>
                    </div>
                  </div>

                  <FormField
                    control={form.control}
                    name="email"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel className="text-sm font-medium">Email</FormLabel>
                        <FormControl>
                          <Input
                            type="email"
                            placeholder="your@email.com"
                            className="h-11"
                            {...field}
                            disabled={isLoading || isGoogleLoading}
                          />
                        </FormControl>
                        <FormMessage className="text-xs" />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="password"
                    render={({ field }) => (
                      <FormItem>
                        <div className="flex items-center justify-between">
                          <FormLabel className="text-sm font-medium">Password</FormLabel>
                          <Link
                            href="/forgot-password"
                            className="text-xs text-primary hover:text-primary/80 transition-colors"
                          >
                            Forgot password?
                          </Link>
                        </div>
                        <FormControl>
                          <Input
                            type="password"
                            placeholder="••••••••"
                            className="h-11"
                            {...field}
                            disabled={isLoading || isGoogleLoading}
                          />
                        </FormControl>
                        <FormMessage className="text-xs" />
                      </FormItem>
                    )}
                  />

                  <Button
                    type="submit"
                    className="w-full h-11"
                    disabled={isLoading || isGoogleLoading}
                  >
                    {isLoading ? (
                      <span className="flex items-center gap-2">
                        <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        Loading...
                      </span>
                    ) : (
                      "Sign In"
                    )}
                  </Button>
                </form>
              </Form>
            </CardContent>
            {/* Hidden for pre-launch */}
            <CardFooter>
            <div className="text-center text-sm text-muted-foreground w-full">
              Don&apos;t have an account?{" "}
              <Link href="/signup" className="font-semibold text-foreground hover:text-muted-foreground transition-colors">
                Sign up
              </Link>
            </div>
          </CardFooter> 
          </Card>
        </div>
      </div>

      {/* Right side - Modern Animated Banner */}
      <AuthBanner
        heading="Welcome to
Your Dashboard"
        description="Join thousands of teams managing their projects efficiently. Collaborate seamlessly with powerful tools designed for modern teams."
        badges={[
          { value: "10K+", label: "Happy Users" },
          { value: "99%", label: "Uptime" },
          { value: "24/7", label: "Support" },
        ]}
      />
    </div>
  );
}