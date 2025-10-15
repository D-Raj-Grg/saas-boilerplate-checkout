"use client";

import { useState } from "react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { Shield, Eye, EyeOff, Check, Loader2, AlertCircle } from "lucide-react";

interface AccountSecuritySectionProps {
  onPasswordChange: (data: {
    old_password: string;
    new_password: string;
    new_password_confirmation: string;
  }) => Promise<boolean>;
}

export function AccountSecuritySection({
  onPasswordChange
}: AccountSecuritySectionProps) {
  const [showPasswords, setShowPasswords] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [formData, setFormData] = useState({
    old_password: "",
    new_password: "",
    new_password_confirmation: "",
  });
  const [errors, setErrors] = useState<Record<string, string>>({});

  const validateForm = () => {
    const newErrors: Record<string, string> = {};

    if (!formData.old_password) {
      newErrors.old_password = "Current password is required";
    }

    if (!formData.new_password) {
      newErrors.new_password = "New password is required";
    } else if (formData.new_password.length < 8) {
      newErrors.new_password = "New password must be at least 8 characters";
    }

    if (!formData.new_password_confirmation) {
      newErrors.new_password_confirmation = "Password confirmation is required";
    } else if (formData.new_password !== formData.new_password_confirmation) {
      newErrors.new_password_confirmation = "Passwords do not match";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validateForm()) {
      setSaveStatus('error');
      return;
    }

    setIsLoading(true);
    setSaveStatus('saving');

    try {
      const success = await onPasswordChange({
        old_password: formData.old_password,
        new_password: formData.new_password,
        new_password_confirmation: formData.new_password_confirmation,
      });

      if (success) {
        setSaveStatus('saved');
        // Clear form on success
        setFormData({
          old_password: "",
          new_password: "",
          new_password_confirmation: "",
        });
        setTimeout(() => setSaveStatus('idle'), 3000);
      } else {
        setSaveStatus('error');
      }
    } catch {
      setSaveStatus('error');
    } finally {
      setIsLoading(false);
    }
  };

  const handleChange = (field: keyof typeof formData) => (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData(prev => ({
      ...prev,
      [field]: e.target.value,
    }));

    // Clear errors when user starts typing
    if (errors[field]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[field];
        return newErrors;
      });
    }

    setSaveStatus('idle');
  };

  const isFormFilled = formData.old_password || formData.new_password || formData.new_password_confirmation;

  const getPasswordStrength = (password: string) => {
    if (!password) return { score: 0, text: '' };

    let score = 0;
    const feedback = [];

    if (password.length >= 8) score++;
    else feedback.push('8+ characters');

    if (/[a-z]/.test(password)) score++;
    else feedback.push('lowercase letter');

    if (/[A-Z]/.test(password)) score++;
    else feedback.push('uppercase letter');

    if (/\d/.test(password)) score++;
    else feedback.push('number');

    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
    else feedback.push('special character');

    const strength = score < 2 ? 'Weak' : score < 4 ? 'Medium' : 'Strong';
    const color = score < 2 ? 'text-red-600' : score < 4 ? 'text-amber-600' : 'text-green-600';

    return {
      score,
      strength,
      color,
      feedback: feedback.slice(0, 3), // Show max 3 suggestions
    };
  };

  const passwordStrength = getPasswordStrength(formData.new_password);

  return (
    <div id="account-security" className="grid grid-cols-1 lg:grid-cols-3 gap-8 py-8 border-b">
      {/* Left Column - Title & Description */}
      <div className="lg:col-span-1">
        <div className="flex items-center gap-3 mb-3">
          <Shield className="h-5 w-5 text-[#005F5A]" />
          <h2 className="text-lg font-semibold">Account & Security</h2>
        </div>
        <p className="text-sm text-muted-foreground">
          Update your password and manage security settings. All password changes require your current password for verification.
        </p>

        {/* Status Messages */}
        <div className="mt-4 space-y-2">
          {saveStatus === 'saved' && (
            <span className="text-xs font-medium text-green-600 flex items-center gap-1">
              <Check className="h-3 w-3" />
              Password updated successfully
            </span>
          )}
          {saveStatus === 'error' && (
            <span className="text-xs font-medium text-red-600 flex items-center gap-1">
              <AlertCircle className="h-3 w-3" />
              Failed to update password
            </span>
          )}
        </div>
      </div>

      {/* Right Column - Form Controls */}
      <div className="lg:col-span-2">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Password Change Section Header */}
          <div className="flex items-center justify-between">
            <div>
              <h3 className="font-semibold">Change Password</h3>
              <p className="text-sm text-muted-foreground">
                Update your password to keep your account secure
              </p>
            </div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={() => setShowPasswords(!showPasswords)}
              className="text-muted-foreground hover:text-foreground"
            >
              {showPasswords ? (
                <>
                  <EyeOff className="h-4 w-4 mr-1" />
                  Hide
                </>
              ) : (
                <>
                  <Eye className="h-4 w-4 mr-1" />
                  Show
                </>
              )}
            </Button>
          </div>

          {/* Password Fields */}
          <div className="space-y-4">
            {/* Current Password */}
            <div className="space-y-2">
                  <Label htmlFor="old_password" className="text-sm font-medium">
                    Current Password *
                  </Label>
                  <Input
                    id="old_password"
                    type={showPasswords ? "text" : "password"}
                    value={formData.old_password}
                    onChange={handleChange("old_password")}
                    disabled={isLoading}
                    className={errors.old_password ? "border-destructive" : ""}
                    placeholder="Enter your current password"
                  />
                  {errors.old_password && (
                    <p className="text-xs text-destructive flex items-center gap-1">
                      <AlertCircle className="h-3 w-3" />
                      {errors.old_password}
                    </p>
                  )}
                </div>

                {/* New Password */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="new_password" className="text-sm font-medium">
                      New Password *
                    </Label>
                    <Input
                      id="new_password"
                      type={showPasswords ? "text" : "password"}
                      value={formData.new_password}
                      onChange={handleChange("new_password")}
                      disabled={isLoading}
                      className={errors.new_password ? "border-destructive" : ""}
                      placeholder="Enter new password"
                    />
                    {formData.new_password && (
                      <div className="space-y-1">
                        <p className={`text-xs font-medium ${passwordStrength.color}`}>
                          Strength: {passwordStrength.strength}
                        </p>
                        {passwordStrength.feedback && passwordStrength.feedback.length > 0 && (
                          <p className="text-xs text-muted-foreground">
                            Add: {passwordStrength.feedback.join(', ')}
                          </p>
                        )}
                      </div>
                    )}
                    {errors.new_password && (
                      <p className="text-xs text-destructive flex items-center gap-1">
                        <AlertCircle className="h-3 w-3" />
                        {errors.new_password}
                      </p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="new_password_confirmation" className="text-sm font-medium">
                      Confirm New Password *
                    </Label>
                    <Input
                      id="new_password_confirmation"
                      type={showPasswords ? "text" : "password"}
                      value={formData.new_password_confirmation}
                      onChange={handleChange("new_password_confirmation")}
                      disabled={isLoading}
                      className={errors.new_password_confirmation ? "border-destructive" : ""}
                      placeholder="Confirm new password"
                    />
                    {formData.new_password_confirmation && formData.new_password === formData.new_password_confirmation && (
                      <p className="text-xs text-green-600 flex items-center gap-1">
                        <Check className="h-3 w-3" />
                        Passwords match
                      </p>
                    )}
                    {errors.new_password_confirmation && (
                      <p className="text-xs text-destructive flex items-center gap-1">
                        <AlertCircle className="h-3 w-3" />
                        {errors.new_password_confirmation}
                      </p>
                    )}
                  </div>
                </div>

                {/* Password Requirements */}
                <div className="text-xs bg-muted/50 p-3 rounded-md">
                  <p className="font-medium mb-2">Password requirements:</p>
                  <ul className="space-y-1 text-muted-foreground">
                    <li className={`flex items-center gap-2 ${formData.new_password.length >= 8 ? 'text-green-600' : ''}`}>
                      {formData.new_password.length >= 8 ? <Check className="h-3 w-3" /> : '•'} At least 8 characters long
                    </li>
                    <li className={`flex items-center gap-2 ${/[a-z]/.test(formData.new_password) && /[A-Z]/.test(formData.new_password) ? 'text-green-600' : ''}`}>
                      {/[a-z]/.test(formData.new_password) && /[A-Z]/.test(formData.new_password) ? <Check className="h-3 w-3" /> : '•'} Mix of uppercase and lowercase letters
                    </li>
                    <li className={`flex items-center gap-2 ${/\d/.test(formData.new_password) ? 'text-green-600' : ''}`}>
                      {/\d/.test(formData.new_password) ? <Check className="h-3 w-3" /> : '•'} At least one number
                    </li>
                  </ul>
                </div>

                {/* Action Buttons */}
                <div className="flex items-center justify-between pt-4">
                  <div className="text-sm">
                    {saveStatus === 'saved' && (
                      <span className="text-green-600 flex items-center gap-1">
                        <Check className="h-4 w-4" />
                        Password updated successfully
                      </span>
                    )}
                    {saveStatus === 'error' && (
                      <span className="text-red-600 flex items-center gap-1">
                        <AlertCircle className="h-4 w-4" />
                        Failed to update password
                      </span>
                    )}
                  </div>

                  <div className="flex gap-2">
                    {isFormFilled && (
                      <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                          setFormData({
                            old_password: "",
                            new_password: "",
                            new_password_confirmation: "",
                          });
                          setErrors({});
                          setSaveStatus('idle');
                        }}
                        disabled={isLoading}
                        size="sm"
                      >
                        Clear
                      </Button>
                    )}

                    <Button
                      type="submit"
                      disabled={!isFormFilled || isLoading}
                      size="sm"
                    >
                      {isLoading ? (
                        <>
                          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                          Updating Password...
                        </>
                      ) : (
                        "Update Password"
                      )}
                    </Button>
                  </div>
              </div>
            </div>

            <Separator />

          {/* Future Security Features */}
          <div className="space-y-4">
            <h3 className="font-semibold text-sm">Additional Security</h3>
            <div className="bg-muted/50 p-4 rounded-lg">
              <p className="text-sm text-muted-foreground">
                Two-factor authentication and other advanced security features are coming soon.
              </p>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}