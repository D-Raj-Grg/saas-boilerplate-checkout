"use client";

import React from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import {
  UserProfile,
  updateUserProfile,
  UpdateProfileData,
} from "@/actions/profile";
import { PersonalInfoSection } from "./personal-info";
import { AccountSecuritySection } from "./account-security";

interface UnifiedProfileFormProps {
  initialProfile?: UserProfile;
}

export function UnifiedProfileForm({
  initialProfile,
}: UnifiedProfileFormProps) {
  const router = useRouter();

  // Handler for profile updates
  const handleProfileSave = async (data: {
    first_name: string;
    last_name: string;
  }): Promise<boolean> => {
    try {
      const updateData: UpdateProfileData = {
        first_name: data.first_name,
        last_name: data.last_name,
      };

      const result = await updateUserProfile(updateData);

      if (result.success) {
        toast.success("Profile updated", {
          description: "Your personal information has been saved.",
        });
        router.refresh();
        return true;
      } else {
        toast.error("Update failed", {
          description: result.error || "Failed to update profile",
        });
        return false;
      }
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred",
      });
      return false;
    }
  };

  // Handler for password changes
  const handlePasswordChange = async (data: {
    old_password: string;
    new_password: string;
    new_password_confirmation: string;
  }): Promise<boolean> => {
    try {
      const updateData: UpdateProfileData = {
        first_name: initialProfile?.first_name || "",
        last_name: initialProfile?.last_name || "",
        old_password: data.old_password,
        new_password: data.new_password,
        new_password_confirmation: data.new_password_confirmation,
      };

      const result = await updateUserProfile(updateData);

      if (result.success) {
        const message = result.password_changed
          ? "Password updated successfully"
          : "Password update completed";

        toast.success("Security updated", {
          description: message,
        });

        return true;
      } else {
        if (result.errors && typeof result.errors === "object") {
          const errorMessages = Object.entries(result.errors)
            .map(([, value]) => (Array.isArray(value) ? value[0] : value))
            .join(", ");
          toast.error("Password update failed", {
            description: errorMessages,
          });
        } else {
          toast.error("Password update failed", {
            description: result.error || "Failed to update password",
          });
        }
        return false;
      }
    } catch {
      toast.error("Error", {
        description: "An unexpected error occurred",
      });
      return false;
    }
  };

  return (
    <div className="space-y-6 max-w-7xl mx-auto">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">My Profile</h1>
          <p className="text-muted-foreground">
            Manage your personal information and account security settings
          </p>
        </div>
      </div>

      <div className="space-y-0">
        {/* Personal Information */}
        <PersonalInfoSection
          initialProfile={initialProfile}
          onSave={handleProfileSave}
          autoSave={true}
        />

        {/* Account Security */}
        <AccountSecuritySection onPasswordChange={handlePasswordChange} />
      </div>
    </div>
  );
}