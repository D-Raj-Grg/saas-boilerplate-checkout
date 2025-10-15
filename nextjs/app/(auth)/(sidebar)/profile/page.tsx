import { UnifiedProfileForm } from "./_components/unified-profile-form";
import { getUserProfile } from "@/actions/profile";
import { Card } from "@/components/ui/card";
import { AlertCircle } from "lucide-react";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: `My Profile | ${process.env.NEXT_PUBLIC_APP_NAME}`,
  description: "Manage your personal profile information and account security settings",
};

export default async function ProfilePage() {
  // Get user profile data
  const profileResult = await getUserProfile();

  // Handle errors
  if (!profileResult.success) {
    return (
      <div className="max-w-7xl mx-auto p-6">
        <Card className="p-6">
          <div className="flex items-center gap-2 text-destructive">
            <AlertCircle className="h-5 w-5" />
            <p>{profileResult.error || "Failed to load profile data"}</p>
          </div>
        </Card>
      </div>
    );
  }

  return (
    <UnifiedProfileForm
      initialProfile={profileResult.data}
    />
  );
}