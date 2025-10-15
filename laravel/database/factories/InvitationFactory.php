<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
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
            'email' => $this->faker->unique()->safeEmail(),
            'role' => $this->faker->randomElement(['member', 'admin', 'owner']),
            'status' => 'pending',
            'message' => $this->faker->sentence(),
            'inviter_id' => User::factory(),
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Indicate that the invitation should be for a specific workspace.
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Indicate that the invitation should be sent by a specific user.
     */
    public function sentBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'inviter_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the invitation should be for a specific email.
     */
    public function forEmail(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }

    /**
     * Indicate that the invitation should have a specific role.
     */
    public function withRole(string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }

    /**
     * Indicate that the invitation should have a specific status.
     */
    public function withStatus(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Indicate that the invitation should be expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate that the invitation should be accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    /**
     * Indicate that the invitation should be declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
            'declined_at' => now(),
        ]);
    }
}
