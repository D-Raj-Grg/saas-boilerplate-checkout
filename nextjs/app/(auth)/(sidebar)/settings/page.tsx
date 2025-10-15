import { UnifiedSettingsForm } from "@/app/(auth)/(sidebar)/settings/_components/unified-settings-form";
import { getWorkspaceAction } from "@/actions/workspace";
import { Card } from "@/components/ui/card";
import { AlertCircle } from "lucide-react";

export default async function SettingsPage() {
  // Get workspace data
  const workspaceResult = await getWorkspaceAction();

  // Handle workspace errors
  if (!workspaceResult.success) {
    return (
      <div className="max-w-7xl mx-auto p-6">
        <Card className="p-6">
          <div className="flex items-center gap-2 text-destructive">
            <AlertCircle className="h-5 w-5" />
            <p>{workspaceResult.error || "Failed to load workspace data"}</p>
          </div>
        </Card>
      </div>
    );
  }

  return (
    <UnifiedSettingsForm
      initialWorkspace={workspaceResult.data}
    />
  );
}