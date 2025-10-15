<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Connection>
 */
class ConnectionFactory extends Factory
{
    protected $model = Connection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'integration_name' => $this->faker->randomElement(['wordpress', 'shopify', 'magento']),
            'site_url' => $this->faker->url(),
            'config' => [
                'access_token' => $this->faker->sha256(),
                'refresh_token' => $this->faker->sha256(),
                'expires_at' => now()->addHours(24)->toISOString(),
            ],
            'status' => $this->faker->randomElement(['active', 'inactive', 'error']),
            'last_sync_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the connection is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the connection is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the connection has an error.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
        ]);
    }

    /**
     * Indicate that the connection is for WordPress.
     */
    public function wordpress(): static
    {
        return $this->state(fn (array $attributes) => [
            'integration_name' => 'wordpress',
            'site_url' => 'https://'.$this->faker->domainName(),
        ]);
    }

    /**
     * Set a specific access token.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'config' => array_merge($attributes['config'] ?? [], [
                'access_token' => $token,
            ]),
        ]);
    }
}
