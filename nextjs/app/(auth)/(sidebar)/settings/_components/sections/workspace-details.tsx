"use client";

import { useState, useEffect, useMemo, useCallback } from "react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Building, Save, Check, Loader2 } from "lucide-react";

interface WorkspaceDetailsData {
  name: string;
  description: string;
}

interface WorkspaceDetailsSectionProps {
  initialData?: Partial<WorkspaceDetailsData>;
  onSave: (data: Partial<WorkspaceDetailsData>) => Promise<boolean>;
  autoSave?: boolean;
}

export function WorkspaceDetailsSection({
  initialData,
  onSave,
  autoSave = true
}: WorkspaceDetailsSectionProps) {
  const [formData, setFormData] = useState<WorkspaceDetailsData>({
    name: initialData?.name || "",
    description: initialData?.description || "",
  });
  const [isLoading, setIsLoading] = useState(false);
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [hasChanges, setHasChanges] = useState(false);

  const initialFormData = useMemo(() => ({
    name: initialData?.name || "",
    description: initialData?.description || "",
  }), [initialData]);

  // Check for changes
  useEffect(() => {
    const changed = Object.keys(formData).some(key =>
      formData[key as keyof WorkspaceDetailsData] !== initialFormData[key as keyof WorkspaceDetailsData]
    );
    setHasChanges(changed);
  }, [formData, initialFormData]);

  const validateForm = useCallback(() => {
    const newErrors: Record<string, string> = {};

    if (!formData.name.trim()) {
      newErrors.name = "Workspace name is required";
    } else if (formData.name.trim().length < 2) {
      newErrors.name = "Workspace name must be at least 2 characters";
    }

    if (formData.description && formData.description.length > 500) {
      newErrors.description = "Description must be less than 500 characters";
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
        name: formData.name.trim(),
        description: formData.description.trim(),
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

  const handleChange = (field: keyof WorkspaceDetailsData) => (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
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
    <div id="workspace-details" className="grid grid-cols-1 lg:grid-cols-3 gap-8 py-8 border-b">
      {/* Left Column - Title & Description */}
      <div className="lg:col-span-1">
        <div className="flex items-center gap-3 mb-3">
          <Building className="h-5 w-5 text-[#005F5A]" />
          <h2 className="text-lg font-semibold">Workspace Details</h2>
        </div>
        <p className="text-sm text-muted-foreground">
          Manage your workspace name and description. Changes are automatically saved.
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
        {/* Workspace Name */}
        <div className="space-y-2">
          <Label htmlFor="workspace_name" className="text-sm font-medium">
            Workspace Name *
          </Label>
          <Input
            id="workspace_name"
            value={formData.name}
            onChange={handleChange("name")}
            disabled={isLoading}
            className={errors.name ? "border-destructive focus:border-destructive" : ""}
            placeholder="Enter workspace name"
            maxLength={100}
          />
          {errors.name && (
            <p className="text-xs text-destructive">{errors.name}</p>
          )}
        </div>

        {/* Workspace Description */}
        <div className="space-y-2">
          <Label htmlFor="workspace_description" className="text-sm font-medium">
            Description (Optional)
          </Label>
          <Textarea
            id="workspace_description"
            value={formData.description}
            onChange={handleChange("description")}
            disabled={isLoading}
            className={`resize-none ${errors.description ? "border-destructive focus:border-destructive" : ""}`}
            placeholder="Enter workspace description"
            rows={3}
            maxLength={500}
          />
          <div className="flex justify-between items-center">
            <div>
              {errors.description && (
                <p className="text-xs text-destructive">{errors.description}</p>
              )}
            </div>
            <span className="text-xs text-muted-foreground">
              {formData.description.length}/500 characters
            </span>
          </div>
        </div>

        {/* Manual Save Button (if not auto-save) */}
        {!autoSave && (
          <div className="flex justify-start">
            <Button
              onClick={handleManualSave}
              disabled={isLoading || !hasChanges}
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