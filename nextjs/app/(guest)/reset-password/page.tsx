import { ResetPasswordForm } from "./_components/reset-password-form";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `Reset Password | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Create a new password for your account",
};

export default function ResetPasswordPage() {
  return <ResetPasswordForm />;
}