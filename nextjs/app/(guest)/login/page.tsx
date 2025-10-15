import { Suspense } from "react";
import { LoginForm } from "./_components/login-form";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Login | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: `Sign in to your ${process.env.NEXT_PUBLIC_APP_NAME} account`,
};

export default function LoginPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-background flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    }>
      <LoginForm />
    </Suspense>
  );
}