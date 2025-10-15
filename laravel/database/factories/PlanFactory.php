<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate unique slug to avoid conflicts with seeded data
        $name = $this->faker->company().' '.$this->faker->randomElement(['Plan', 'Package', 'Bundle']);
        $slug = str($name)->slug().'-'.$this->faker->randomNumber(5);

        $price = $this->faker->randomFloat(2, 0, 999.99);

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $this->faker->sentence(),
            'price' => $price,
            'priority' => $this->faker->numberBetween(900, 1100),
            'max_price' => $price, // Default max_price to same as price
            'billing_cycle' => $this->faker->randomElement(['monthly', 'yearly', 'lifetime']),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the plan should be free.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0.00,
            'priority' => 997,
            'max_price' => 0.00,
        ]);
    }

    /**
     * Indicate that the plan should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
