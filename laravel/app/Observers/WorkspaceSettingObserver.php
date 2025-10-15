<?php

namespace App\Observers;

use App\Models\WorkspaceSetting;
use Illuminate\Support\Facades\Log;

class WorkspaceSettingObserver
{
    /**
     * Handle the WorkspaceSetting "created" event.
     */
    public function created(WorkspaceSetting $workspaceSetting): void
    {
        /** @var \App\Models\Workspace|null $workspace */
        $workspace = $workspaceSetting->workspace;

        if (! $workspace) {
            Log::warning('WorkspaceSetting created but workspace not found', [
                'workspace_setting_id' => $workspaceSetting->id,
                'workspace_id' => $workspaceSetting->workspace_id,
            ]);

            return;
        }

        Log::info('Workspace settings created', [
            'workspace_uuid' => $workspace->uuid,
            'workspace_id' => $workspaceSetting->workspace_id,
        ]);
    }

    /**
     * Handle the WorkspaceSetting "updated" event.
     */
    public function updated(WorkspaceSetting $workspaceSetting): void
    {
        /** @var \App\Models\Workspace|null $workspace */
        $workspace = $workspaceSetting->workspace;

        if (! $workspace) {
            Log::warning('WorkspaceSetting updated but workspace not found', [
                'workspace_setting_id' => $workspaceSetting->id,
                'workspace_id' => $workspaceSetting->workspace_id,
            ]);

            return;
        }

        // Check if settings actually changed
        $originalSettings = $workspaceSetting->getOriginal('settings');
        $settingsChanged = $originalSettings !== $workspaceSetting->settings;

        if ($settingsChanged) {
            Log::info('Workspace settings changed', [
                'workspace_uuid' => $workspace->uuid,
                'workspace_id' => $workspaceSetting->workspace_id,
            ]);
        }
    }

    /**
     * Handle the WorkspaceSetting "deleted" event.
     */
    public function deleted(WorkspaceSetting $workspaceSetting): void
    {
        /** @var \App\Models\Workspace|null $workspace */
        $workspace = $workspaceSetting->workspace;

        if (! $workspace) {
            Log::warning('WorkspaceSetting deleted but workspace not found', [
                'workspace_setting_id' => $workspaceSetting->id,
                'workspace_id' => $workspaceSetting->workspace_id,
            ]);

            return;
        }

        Log::info('Workspace settings deleted', [
            'workspace_uuid' => $workspace->uuid,
            'workspace_id' => $workspaceSetting->workspace_id,
        ]);
    }

    /**
     * Handle the WorkspaceSetting "force deleted" event..
     */
    public function forceDeleted(WorkspaceSetting $workspaceSetting): void
    {
        $this->deleted($workspaceSetting);
    }
}
