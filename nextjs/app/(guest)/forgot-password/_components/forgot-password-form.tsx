"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { forgotPasswordAction } from "@/actions/auth";
import { AuthBanner } from "@/components/auth/auth-banner";
import Image from "next/image";

const forgotPasswordSchema = z.object({
  email: z.string().email("Please enter a valid email address"),
});

type ForgotPasswordFormValues = z.infer<typeof forgotPasswordSchema>;

export function ForgotPasswordForm() {
  const router = useRouter();
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const form = useForm<ForgotPasswordFormValues>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: {
      email: "",
    },
  });

  async function onSubmit(data: ForgotPasswordFormValues) {
    try {
      setIsLoading(true);
      setError(null);

      const result = await forgotPasswordAction(data);

      if (result.success) {
        // Show success toast
        toast.success("Reset link sent!", {
          description: `If the email exists, a reset link has been sent to ${data.email}. Please check your email and spam folder.`,
          duration: 8000,
        });

        // Reset form
        form.reset();

        // Redirect to login with success message
        router.push("/login");
      } else {
        setError(result.error || "Failed to send reset link");
      }
    } catch {
      setError("An unexpected error occurred. Please try again.");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <div className="flex h-screen">
      {/* Left side - Forgot Password Form */}
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
              Forgot Password?
            </CardTitle>
            <CardDescription className="text-base">
              Enter your email and we&apos;ll send you a reset link
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
                      Sending...
                    </span>
                  ) : (
                    "Send Reset Link"
                  )}
                </Button>
              </form>
            </Form>
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
        heading="Reset Made Simple"
        description="Do not worry, it happens to the best of us. We'll send you a secure reset link to get you back into your account quickly and safely."
        badges={[
          { value: "Secure", label: "Password Reset" },
          { value: "Quick", label: "Email Delivery" },
          { value: "24/7", label: "Support" },
        ]}
      />
    </div>
  );
}