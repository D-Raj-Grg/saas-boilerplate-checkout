<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \App\Models\Plan $resource
 */
class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->uuid ?? $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'price' => $this->resource->price,
            'priority' => $this->resource->priority,
            'max_price' => $this->resource->max_price,
            'billing_cycle' => $this->resource->billing_cycle,
            'is_active' => $this->resource->is_active,
        ];
    }
}
