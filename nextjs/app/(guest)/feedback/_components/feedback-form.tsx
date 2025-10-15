"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { toast } from "sonner";
import Link from "next/link";
import { ArrowRight, MessageSquare, CheckCircle2 } from "lucide-react";
import { submitFeedbackAction } from "@/actions/feedback";
import { Logo } from "@/components/ui/logo";

const feedbackSchema = z.object({
  email: z.string().email("Please enter a valid email address"),
  feedback: z.string().min(10, "Please provide at least 10 characters of feedback"),
});

type FeedbackFormValues = z.infer<typeof feedbackSchema>;

export function FeedbackForm() {
  const [isLoading, setIsLoading] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const form = useForm<FeedbackFormValues>({
    resolver: zodResolver(feedbackSchema),
    defaultValues: {
      email: "",
      feedback: "",
    },
  });

  async function onSubmit(data: FeedbackFormValues) {
    try {
      setIsLoading(true);

      const result = await submitFeedbackAction(data);

      if (result.success) {
        setIsSuccess(true);
        form.reset();
        toast.success("Thank you for your feedback! We appreciate your input.");
      } else {
        toast.error(result.error || "Failed to submit feedback");
      }
    } catch {
      toast.error("An unexpected error occurred");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <div className="min-h-screen bg-background flex items-center justify-center px-4 py-8 sm:px-6 sm:py-16 lg:px-8 lg:py-16 relative overflow-hidden">
      {/* Background decorative elements */}
      <div className="absolute inset-0 overflow-hidden">
        <div className="absolute -top-40 -right-40 w-96 h-96 bg-muted rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div className="absolute -bottom-40 -left-40 w-96 h-96 bg-accent rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div className="absolute top-40 left-40 w-96 h-96 bg-primary/10 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
      </div>

      <div className="relative z-10 max-w-2xl w-full space-y-4">
        {/* Logo */}
        <div className="flex justify-center">
          <Link href="/" className="group">
            <div className="h-10 transform transition-transform group-hover:scale-105">
              <Logo />
            </div>
          </Link>
        </div>

        {/* Main content */}
        <div className="text-center space-y-6">
          <div className="space-y-4">
            <h1 className="text-5xl sm:text-6xl font-bold text-transparent bg-clip-text bg-gradient-to-r to-muted-foreground from-primary tracking-tight leading-16">
              We Value Your
              <span className="block">
                Feedback
              </span>
            </h1>
            <p className="text-xl text-muted-foreground max-w-xl mx-auto leading-relaxed">
              Help us improve {process.env.NEXT_PUBLIC_APP_NAME}. Share your thoughts, suggestions, or report issues you&apos;ve encountered.
            </p>
          </div>

          {/* Benefits */}
          <div className="flex flex-wrap justify-center gap-6 text-sm text-muted-foreground py-6">
            <div className="flex items-center gap-2">
              <CheckCircle2 className="h-4 w-4 text-primary" />
              <span>Quick Response</span>
            </div>
            <div className="flex items-center gap-2">
              <CheckCircle2 className="h-4 w-4 text-primary" />
              <span>Direct to Team</span>
            </div>
            <div className="flex items-center gap-2">
              <CheckCircle2 className="h-4 w-4 text-primary" />
              <span>Shape the Product</span>
            </div>
          </div>

          {/* Form */}
          {!isSuccess ? (
            <div className="max-w-md mx-auto">
              <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                  <FormField
                    control={form.control}
                    name="email"
                    render={({ field }) => (
                      <FormItem className="text-left">
                        <FormLabel className="text-foreground">Your Email</FormLabel>
                        <FormControl>
                          <Input
                            type="email"
                            placeholder="Enter your email address"
                            className="h-12 px-4 text-base border-2 rounded-xl transition-all duration-300 focus-visible:border-primary focus:border-primary focus:shadow-lg focus:shadow-primary/10"
                            disabled={isLoading}
                            autoFocus={true}
                            {...field}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <FormField
                    control={form.control}
                    name="feedback"
                    render={({ field }) => (
                      <FormItem className="text-left">
                        <FormLabel className="text-foreground">Your Feedback</FormLabel>
                        <FormControl>
                          <Textarea
                            placeholder="Share your thoughts, suggestions, or report any issues..."
                            className="min-h-[150px] px-4 py-3 text-base border-2 rounded-xl transition-all duration-300 focus-visible:border-primary focus:border-primary focus:shadow-lg focus:shadow-primary/10 resize-none"
                            disabled={isLoading}
                            {...field}
                          />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />

                  <Button
                    type="submit"
                    disabled={isLoading}
                    className="w-full h-12 rounded-xl transition-all duration-300 hover:shadow-lg hover:shadow-primary/20"
                  >
                    {isLoading ? (
                      <>
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                        Submitting...
                      </>
                    ) : (
                      <>
                        Submit Feedback
                        <ArrowRight className="ml-2 h-4 w-4 group-hover:translate-x-1 transition-transform" />
                      </>
                    )}
                  </Button>
                </form>
              </Form>

              <p className="text-xs text-muted-foreground mt-4 text-center">
                We read every feedback and will get back to you if needed.
              </p>
            </div>
          ) : (
            <div className="max-w-md mx-auto bg-transparent border border-grey-300 rounded-2xl p-8 text-center space-y-4">
              <div className="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-full">
                <MessageSquare className="h-8 w-8 text-primary-foreground" />
              </div>
              <h3 className="text-2xl font-bold text-foreground">Thank You!</h3>
              <p className="text-muted-foreground">
                Your feedback has been received. We truly appreciate you taking the time to help us improve {process.env.NEXT_PUBLIC_APP_NAME}.
              </p>
              <Button
                onClick={() => setIsSuccess(false)}
                variant="outline"
                className="rounded-full border-2 border-primary text-primary hover:bg-primary hover:text-primary-foreground transition-all duration-300"
              >
                Send More Feedback
              </Button>
            </div>
          )}
        </div>

        {/* Footer links */}
        <div className="flex justify-center gap-6 pt-8">
          <Link
            href="/join-waitlist"
            className="text-sm text-muted-foreground hover:text-primary transition-colors duration-300"
          >
            Join Waitlist →
          </Link>
          <Link
            href="/login"
            className="text-sm text-muted-foreground hover:text-primary transition-colors duration-300"
          >
            Sign in →
          </Link>
        </div>
      </div>
    </div>
  );
}