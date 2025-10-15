import { VerifyEmailForm } from "@/app/(guest)/verify-email/_components/verify-email-form";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Verify Email | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Verify your email address",
};

export default function VerifyEmailPage() {
  return <VerifyEmailForm />;
}