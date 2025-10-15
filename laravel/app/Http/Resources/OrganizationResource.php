<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Organization $resource
 */
class OrganizationResource extends JsonResource
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
            'owner_id' => $this->owner->uuid,
            'is_owner' => $this->when($request->user() !== null, fn () => $request->user()?->isOrganizationOwner($this->resource)),
            'permissions' => $this->when($request->user() !== null, function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return [];
                }
                /** @var \App\Models\Organization $organization */
                $organization = $this->resource;

                return $user->getOrganizationPermissions($organization);
            }),
            'plan' => new PlanResource($this->resource->getCurrentPlan()),
            'workspaces' => WorkspaceResource::collection($this->whenLoaded('workspaces')),
            'workspaces_count' => $this->when(isset($this->workspaces_count), $this->workspaces_count, fn () => $this->workspaces()->count()),
            'members_count' => $this->when(isset($this->members_count), $this->members_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
