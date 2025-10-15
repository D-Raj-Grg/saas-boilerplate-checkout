<?php

namespace App\Http\Resources;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Workspace $resource
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \App\Models\Organization $organization
 * @property-read int|null $users_count
 * @property-read (\Illuminate\Database\Eloquent\Relations\Pivot&object{role: string, joined_at: \Carbon\Carbon|null})|null $pivot
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @mixin Workspace
 */
class WorkspaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'organization_id' => $this->organization->uuid,
            'organization_name' => $this->organization->name,
            'organization' => [
                'uuid' => $this->organization->uuid,
                'name' => $this->organization->name,
                'slug' => $this->organization->slug,
                'plan' => ($currentPlan = $this->organization->getCurrentPlan()) ? [
                    'name' => $currentPlan->name,
                    'slug' => $currentPlan->slug,
                ] : null,
            ],
            'role' => $this->whenPivotLoaded('workspace_users', function () {
                return $this->pivot?->role;
            }),
            'joined_at' => $this->whenPivotLoaded('workspace_users', function () {
                return $this->pivot?->joined_at;
            }),
            'is_owner' => $this->whenPivotLoaded('workspace_users', function () {
                return $this->pivot?->role === WorkspaceRole::MANAGER->value;
            }),
            'members_count' => $this->when(isset($this->users_count), $this->users_count, fn () => $this->users()->count()),
            'permissions' => $this->when($request->user() && $this->pivot, function () use ($request) {
                $user = $request->user();
                /** @var Workspace $workspace */
                $workspace = $this->resource;

                return $user ? $this->getUserPermissions($user, $workspace) : [];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get user permissions for the workspace.
     */
    /**
     * @param  \App\Models\User  $user
     * @param  Workspace  $workspace
     * @return array<string, bool>
     */
    private function getUserPermissions($user, $workspace): array
    {
        return $user->getWorkspacePermissions($workspace);
    }
}
