<?php

namespace Database\Factories;

use App\Models\Waitlist;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaitlistFactory extends Factory
{
    protected $model = Waitlist::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'metadata' => $this->faker->randomElement([
                null,
                ['source' => 'landing_page'],
                ['utm_source' => 'google', 'utm_medium' => 'cpc'],
                ['referrer' => 'https://example.com'],
            ]),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'status' => $this->faker->randomElement(['pending', 'contacted', 'converted']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'contacted_at' => null,
            'converted_at' => null,
        ]);
    }

    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'contacted',
            'contacted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'converted_at' => null,
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
            'contacted_at' => $this->faker->dateTimeBetween('-2 weeks', '-1 week'),
            'converted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}
