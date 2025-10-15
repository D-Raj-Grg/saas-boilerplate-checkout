<?php

namespace App\Observers;

use App\Models\Organization;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;

class WorkspaceObserver
{
    /**
     * Handle the Workspace "created" event.
     */
    public function created(Workspace $workspace): void
    {
        // Consume workspace feature for plan limits
        /** @var Organization|null $organization */
        $organization = $workspace->organization;
        if ($organization) {
            $organization->consumeFeature('workspaces', 1);
        }

        Log::info('Workspace created - consumed plan feature', [
            'workspace_uuid' => $workspace->uuid,
            'organization_id' => $workspace->organization_id,
        ]);
    }

    /**
     * Handle the Workspace "deleted" event.
     */
    public function deleted(Workspace $workspace): void
    {
        // Unconsume workspace feature for plan limits
        /** @var Organization|null $organization */
        $organization = $workspace->organization;
        if ($organization) {
            $organization->unconsumeFeature('workspaces', 1);
        }

        Log::info('Workspace deleted - unconsumed plan feature', [
            'workspace_uuid' => $workspace->uuid,
            'organization_id' => $workspace->organization_id,
        ]);
    }

    /**
     * Handle the Workspace "force deleted" event.
     */
    public function forceDeleted(Workspace $workspace): void
    {
        $this->deleted($workspace);
    }
}
