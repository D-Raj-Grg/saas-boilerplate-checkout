<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkspaceSetting>
 */
class WorkspaceSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'settings' => [
                'default_traffic_split' => 50,
                'auto_stop_enabled' => false,
                'minimum_sample_size' => 100,
                'confidence_level' => 95,
                'timezone' => 'UTC',
                'notification_preferences' => [
                    'member_joined' => true,
                    'member_left' => true,
                    'workspace_updated' => true,
                    'important_updates' => true,
                ],
            ],
        ];
    }

    /**
     * Indicate that the settings should belong to a specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Create settings with specific configuration.
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => array_merge($attributes['settings'], $settings),
        ]);
    }
}
