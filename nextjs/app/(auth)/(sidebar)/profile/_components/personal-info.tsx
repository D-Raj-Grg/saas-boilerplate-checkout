"use client";

import { useState, useEffect, useMemo, useCallback } from "react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { User, Save, Check, Loader2 } from "lucide-react";
import { UserProfile } from "@/actions/profile";

interface PersonalInfoSectionProps {
  initialProfile?: UserProfile;
  onSave: (data: { first_name: string; last_name: string }) => Promise<boolean>;
  autoSave?: boolean;
}

export function PersonalInfoSection({
  initialProfile,
  onSave,
  autoSave = true
}: PersonalInfoSectionProps) {
  const [formData, setFormData] = useState({
    first_name: initialProfile?.first_name || "",
    last_name: initialProfile?.last_name || "",
  });
  const [isLoading, setIsLoading] = useState(false);
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [hasChanges, setHasChanges] = useState(false);

  const initialData = useMemo(() => ({
    first_name: initialProfile?.first_name || "",
    last_name: initialProfile?.last_name || "",
  }), [initialProfile]);

  // Check for changes
  useEffect(() => {
    const changed = Object.keys(formData).some(key =>
      formData[key as keyof typeof formData] !== initialData[key as keyof typeof initialData]
    );
    setHasChanges(changed);
  }, [formData, initialData]);

  const validateForm = useCallback(() => {
    const newErrors: Record<string, string> = {};

    if (!formData.first_name.trim()) {
      newErrors.first_name = "First name is required";
    }
    if (!formData.last_name.trim()) {
      newErrors.last_name = "Last name is required";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  }, [formData]);

  const handleAutoSave = useCallback(async () => {
    if (!validateForm()) {
      setSaveStatus('error');
      return;
    }

    setSaveStatus('saving');
    setIsLoading(true);

    try {
      const success = await onSave({
        first_name: formData.first_name.trim(),
        last_name: formData.last_name.trim(),
      });

      if (success) {
        setSaveStatus('saved');
        setHasChanges(false);
        setTimeout(() => setSaveStatus('idle'), 2000);
      } else {
        setSaveStatus('error');
      }
    } catch {
      setSaveStatus('error');
    } finally {
      setIsLoading(false);
    }

    setSaveStatus('idle');
  }, [onSave, formData, validateForm]);

  // Auto-save logic
  useEffect(() => {
    if (!autoSave || !hasChanges || saveStatus === 'saving') return;

    const timer = setTimeout(() => {
      handleAutoSave();
    }, 2000); // Auto-save after 2 seconds of no typing

    return () => clearTimeout(timer);
  }, [formData, hasChanges, autoSave, saveStatus, handleAutoSave]);

  const handleManualSave = async () => {
    await handleAutoSave();
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


  const getSaveStatusText = () => {
    switch (saveStatus) {
      case 'saving':
        return 'Saving...';
      case 'saved':
        return 'Saved';
      case 'error':
        return 'Error saving';
      default:
        return hasChanges ? 'Unsaved changes' : '';
    }
  };

  const getSaveStatusColor = () => {
    switch (saveStatus) {
      case 'saving':
        return 'text-blue-600';
      case 'saved':
        return 'text-green-600';
      case 'error':
        return 'text-red-600';
      default:
        return hasChanges ? 'text-amber-600' : '';
    }
  };

  return (
    <div id="personal-info" className="grid grid-cols-1 lg:grid-cols-3 gap-8 py-8 border-b">
      {/* Left Column - Title & Description */}
      <div className="lg:col-span-1">
        <div className="flex items-center gap-3 mb-3">
          <User className="h-5 w-5 text-[#005F5A]" />
          <h2 className="text-lg font-semibold">Personal Information</h2>
        </div>
        <p className="text-sm text-muted-foreground">
          Update your personal details and profile information. Changes are automatically saved.
        </p>

        {/* Save Status */}
        {getSaveStatusText() && (
          <div className="mt-4">
            <span className={`text-xs font-medium ${getSaveStatusColor()}`}>
              {saveStatus === 'saving' && <Loader2 className="inline h-3 w-3 animate-spin mr-1" />}
              {saveStatus === 'saved' && <Check className="inline h-3 w-3 mr-1" />}
              {getSaveStatusText()}
            </span>
          </div>
        )}
      </div>

      {/* Right Column - Form Controls */}
      <div className="lg:col-span-2 space-y-6">
        {/* Current Email (Read-only) */}
        <div className="space-y-2">
          <Label className="text-sm font-medium">Email Address</Label>
          <Input
            value={initialProfile?.email || ''}
            disabled
            className="bg-muted text-muted-foreground cursor-not-allowed"
          />
          <p className="text-xs text-muted-foreground">
            Email address cannot be changed
          </p>
        </div>

        {/* Name Fields */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label htmlFor="first_name" className="text-sm font-medium">
              First Name *
            </Label>
            <Input
              id="first_name"
              value={formData.first_name}
              onChange={handleChange("first_name")}
              disabled={isLoading}
              className={errors.first_name ? "border-destructive focus:border-destructive" : ""}
              placeholder="Enter your first name"
            />
            {errors.first_name && (
              <p className="text-xs text-destructive">{errors.first_name}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="last_name" className="text-sm font-medium">
              Last Name *
            </Label>
            <Input
              id="last_name"
              value={formData.last_name}
              onChange={handleChange("last_name")}
              disabled={isLoading}
              className={errors.last_name ? "border-destructive focus:border-destructive" : ""}
              placeholder="Enter your last name"
            />
            {errors.last_name && (
              <p className="text-xs text-destructive">{errors.last_name}</p>
            )}
          </div>
        </div>

        {/* Manual Save Button (if not auto-save) */}
        {!autoSave && hasChanges && (
          <div className="flex justify-end">
            <Button
              onClick={handleManualSave}
              disabled={isLoading}
              size="sm"
            >
              {isLoading ? (
                <Loader2 className="h-4 w-4 animate-spin mr-2" />
              ) : (
                <Save className="h-4 w-4 mr-2" />
              )}
              Save Changes
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}