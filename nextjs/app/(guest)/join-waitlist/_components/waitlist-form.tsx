"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormMessage,
} from "@/components/ui/form";
import { toast } from "sonner";
import Link from "next/link";
import { Sparkles, Check, ChevronRight } from "lucide-react";
import { joinWaitlistAction } from "@/actions/waitlist";
import { useSearchParams } from "next/navigation";
import { Logo } from "@/components/ui/logo";

const waitlistSchema = z.object({
  email: z.string().email("Please enter a valid email address"),
});

type WaitlistFormValues = z.infer<typeof waitlistSchema>;

export function WaitlistForm() {
  const [isLoading, setIsLoading] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const searchParams = useSearchParams();

  const form = useForm<WaitlistFormValues>({
    resolver: zodResolver(waitlistSchema),
    defaultValues: {
      email: "",
    },
  });

  async function onSubmit(data: WaitlistFormValues) {
    try {
      setIsLoading(true);

      // Collect UTM parameters from URL
      const metadata: Record<string, string> = {
        source: "waitlist_page",
      };

      // Add UTM parameters if present
      const utmParams = [
        "utm_source",
        "utm_medium",
        "utm_campaign",
        "utm_content",
        "utm_term",
      ];
      utmParams.forEach((param) => {
        const value = searchParams.get(param);
        if (value) {
          metadata[param] = value;
        }
      });

      // Add referrer if available
      if (typeof document !== "undefined" && document.referrer) {
        metadata.referrer = document.referrer;
      }

      const waitlistData = {
        email: data.email,
        metadata,
      };

      const result = await joinWaitlistAction(waitlistData);

      if (result.success) {
        setIsSuccess(true);
        form.reset();
        toast.success("You're on the list! We'll notify you when we launch.");
      } else {
        toast.error(result.error || "Failed to join waitlist");
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <div className="min-h-screen max-w-7xl mx-auto bg-background flex items-center justify-center px-4 py-8 sm:px-6 sm:py-16 lg:px-8 lg:py-16 relative overflow-hidden">
      <div className="relative z-10 w-full space-y-8">
        {/* Logo */}
        <div className="flex justify-center">
          <Link href="/" className="group">
            <div className="h-10 transform transition-transform group-hover:scale-105">
              <Logo />
            </div>
          </Link>
        </div>

        {/* Main content */}
        <div className="text-center ">
          <div className="space-y-6">
            <h1 className="text-5xl text-center md:text-7xl font-bold ">
              The Future of
              <span className="block w-full text-nowrap">
                Team Collaboration is Coming
              </span>
            </h1>
            <p className="text-xl text-muted-foreground max-w-xl mx-auto leading-relaxed">
              Join the waitlist to be among the first to experience powerful
              collaboration tools designed for modern teams.
            </p>
          </div>

          {/* Benefits */}
          <div className="flex flex-wrap justify-center gap-6 font-medium py-8">
            <div className="flex items-center gap-2">
              <div className="size-4 rounded-full bg-green-600 flex items-center justify-center">
                <Check className="size-full p-0.5 text-white" />
              </div>
              <span>Early Access</span>
            </div>
            <div className="flex items-center gap-2">
              <div className="size-4 rounded-full bg-green-600 flex items-center justify-center">
                <Check className="size-full p-0.5 text-white" />
              </div>
              <span>Exclusive Pricing</span>
            </div>
            <div className="flex items-center gap-2">
              <div className="size-4 rounded-full bg-green-600 flex items-center justify-center">
                <Check className="size-full p-0.5 text-white" />
              </div>
              <span>Priority Support</span>
            </div>
          </div>

          {/* Form */}
          {!isSuccess ? (
            <div className="max-w-md mx-auto">
              <Form {...form}>
                <form
                  onSubmit={form.handleSubmit(onSubmit)}
                  className="space-y-4"
                >
                  <FormField
                    control={form.control}
                    name="email"
                    render={({ field }) => (
                      <FormItem>
                        <FormControl>
                          <div className="relative group">
                            <Input
                              type="email"
                              placeholder="Enter email address"
                              className="h-14 px-6 text-base pl-4 border-1 rounded-lg transition-all duration-300 focus-visible:border-primary focus:border-primary focus:shadow-lg focus:shadow-primary/10 pr-32 relative"
                              disabled={isLoading}
                              autoFocus={true}
                              {...field}
                            />
                            <Button
                              type="submit"
                              disabled={isLoading}
                              className="absolute right-1 top-1 h-12 !px-4 rounded-lg transition-all duration-300 hover:shadow-lg hover:shadow-primary/20"
                            >
                              {isLoading ? (
                                <>
                                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                  Joining...
                                </>
                              ) : (
                                <>
                                  Join Waitlist
                                  <ChevronRight className=" h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                </>
                              )}
                            </Button>
                          </div>
                        </FormControl>
                        <FormMessage className="text-center mt-2" />
                      </FormItem>
                    )}
                  />
                </form>
              </Form>

              <p className="text-sm font-normal text-muted-foreground mt-4 text-center">
                No spam, ever. We&apos;ll only contact you about our launch.
              </p>
            </div>
          ) : (
            <div className="max-w-md mx-auto bg-transparent border border-grey-300 rounded-2xl p-8 text-center space-y-4">
              <div className="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-full">
                <Sparkles className="h-8 w-8 text-primary-foreground" />
              </div>
              <h3 className="text-2xl font-bold text-foreground">
                You&apos;re on the list!
              </h3>
              <p className="text-muted-foreground">
                We&apos;ll send you an exclusive invite when we launch. Get
                ready to transform your team collaboration.
              </p>
              <Button
                onClick={() => setIsSuccess(false)}
                variant="outline"
                className="rounded-full border-2 border-primary text-primary hover:bg-primary hover:text-primary-foreground transition-all duration-300"
              >
                Add Another Email
              </Button>
            </div>
          )}
        </div>

        {/* Stats */}
        <div className="flex justify-center gap-12 ">
          <div className="text-center">
            <div className="text-3xl font-bold text-foreground">100+</div>
            <div className="text-base font-semibold text-muted-foreground">
              Companies Waiting
            </div>
          </div>
          <div className="text-center">
            <div className="text-3xl font-bold text-foreground">Q4 2025</div>
            <div className="text-base font-semibold text-muted-foreground">Expected Launch</div>
          </div>
          <div className="text-center">
            <div className="text-3xl font-bold text-foreground">Up to 20%</div>
            <div className="text-base font-semibold text-muted-foreground">
              Discount on First Year
            </div>
          </div>
        </div>

        {/* Footer link */}
        <div className="text-center pt-8">
          <Link
            href="/login"
            className="text-sm text-muted-foreground hover:text-primary transition-colors duration-300"
          >
            Already have an account? Sign in →
          </Link>
        </div>
      </div>
    </div>
  );
}
