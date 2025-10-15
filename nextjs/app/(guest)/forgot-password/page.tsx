import { ForgotPasswordForm } from "./_components/forgot-password-form";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Forgot Password | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Reset your password",
};

export default function ForgotPasswordPage() {
  return <ForgotPasswordForm />;
}