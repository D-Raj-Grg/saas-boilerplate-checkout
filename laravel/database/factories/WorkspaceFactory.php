<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true).' Workspace';

        return [
            'name' => $name,
            'slug' => str($name)->slug(),
            'description' => $this->faker->sentence(),
            'organization_id' => Organization::factory(),
            'settings' => [],
        ];
    }

    /**
     * Indicate that the workspace should belong to a specific organization.
     */
    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * Create workspace with specific settings.
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => $settings,
        ]);
    }
}
