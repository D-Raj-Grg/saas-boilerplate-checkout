import { Suspense } from "react";
import { SignupForm } from "./_components/signup-form";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Sign Up | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: `Create your ${process.env.NEXT_PUBLIC_APP_NAME} account and start optimizing`,
};

export default function SignupPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-background flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    }>
      <SignupForm />
    </Suspense>
  );
}