import { Suspense } from "react";
import { WaitlistForm } from "./_components/waitlist-form";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Join Waitlist | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Be the first to revolutionize your A/B Testing. Join our exclusive waitlist for early access.",
};

export default function JoinWaitlistPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-black"></div>
      </div>
    }>
      <WaitlistForm />
    </Suspense>
  );
}