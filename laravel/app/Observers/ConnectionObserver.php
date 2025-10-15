<?php

namespace App\Observers;

use App\Models\Connection;
use App\Models\Organization;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;

class ConnectionObserver
{
    /**
     * Handle the Connection "created" event.
     */
    public function created(Connection $connection): void
    {
        // Consume connection feature for plan limits
        /** @var Workspace $workspace */
        $workspace = $connection->workspace;
        /** @var Organization|null $organization */
        $organization = $workspace->organization;
        if ($organization) {
            $organization->consumeFeature('connections_per_workspace', 1, $workspace);
        }

        Log::info('Connection created - consumed plan feature', [
            'connection_uuid' => $connection->uuid ?? $connection->id,
            'workspace_id' => $workspace->id,
            'organization_id' => $workspace->organization_id,
        ]);
    }

    /**
     * Handle the Connection "deleted" event.
     */
    public function deleted(Connection $connection): void
    {
        // Unconsume connection feature for plan limits
        /** @var Workspace $workspace */
        $workspace = $connection->workspace;
        /** @var Organization|null $organization */
        $organization = $workspace->organization;
        if ($organization) {
            $organization->unconsumeFeature('connections_per_workspace', 1, $workspace);
        }

        Log::info('Connection deleted - unconsumed plan feature', [
            'connection_uuid' => $connection->uuid ?? $connection->id,
            'workspace_id' => $workspace->id,
            'organization_id' => $workspace->organization_id,
        ]);
    }

    /**
     * Handle the Connection "force deleted" event.
     */
    public function forceDeleted(Connection $connection): void
    {
        $this->deleted($connection);
    }
}
