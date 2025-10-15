import { Suspense } from "react";
import { FeedbackForm } from "./_components/feedback-form";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Feedback | ${process.env.NEXT_PUBLIC_APP_NAME} - ${process.env.NEXT_PUBLIC_APP_NAME} Platform`,
  description: `Share your feedback to help us improve ${process.env.NEXT_PUBLIC_APP_NAME}. We value your thoughts and suggestions.`,
};

export default function FeedbackPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-black"></div>
      </div>
    }>
      <FeedbackForm />
    </Suspense>
  );
}