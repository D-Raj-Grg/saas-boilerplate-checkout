<?php

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;
        $name = "Test Company {$counter}";

        return [
            'name' => $name,
            'slug' => str($name)->slug(),
            'description' => 'Test organization description',
            'owner_id' => User::factory(),
            'settings' => [],
        ];
    }

    /**
     * Indicate that the organization should have a specific owner.
     */
    public function ownedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $user->id,
        ])->afterCreating(function (Organization $organization) use ($user) {
            // Ensure owner is added to organization_users table
            if (! $organization->hasUser($user)) {
                $organization->addUser($user, OrganizationRole::OWNER);
            }
        });
    }

    /**
     * Indicate that the organization should have a specific plan.
     * This will attach the plan after creating the organization.
     */
    public function withPlan(Plan $plan): static
    {
        return $this->afterCreating(function (Organization $organization) use ($plan) {
            $organization->attachPlan($plan);
        });
    }

    /**
     * Override state method to handle plan_id attribute.
     */
    public function state($state)
    {
        if (is_array($state) && isset($state['plan_id'])) {
            $planId = $state['plan_id'];
            unset($state['plan_id']);

            return parent::state($state)->afterCreating(function (Organization $organization) use ($planId) {
                $plan = Plan::find($planId);
                if ($plan) {
                    $organization->attachPlan($plan);
                }
            });
        }

        return parent::state($state);
    }

    /**
     * Override create method to handle plan_id and ensure organization membership.
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        $planId = null;
        if (is_array($attributes) && array_key_exists('plan_id', $attributes)) {
            $planId = $attributes['plan_id'];
            unset($attributes['plan_id']);
        }

        /** @var Organization $organization */
        $organization = parent::create($attributes, $parent);

        // Add owner to organization_users table (critical for new membership system)
        $owner = $organization->owner;
        if ($owner && ! $organization->hasUser($owner)) {
            $organization->addUser($owner, OrganizationRole::OWNER);
        }

        // Attach plan if plan_id was provided and is not null
        if ($planId) {
            $plan = Plan::find($planId);
            if ($plan) {
                $organization->attachPlan($plan);
            }
        }

        return $organization;
    }

    /**
     * Create organization with specific settings.
     */
    public function withSettings(array $settings): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => $settings,
        ]);
    }
}
