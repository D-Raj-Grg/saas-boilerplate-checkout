<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate plan-related tables (no foreign key constraints to worry about now!)
        DB::table('plan_limits')->truncate();
        DB::table('plan_features')->truncate();
        Plan::truncate();

        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Free plan with basic features',
                'price' => 0.00,
                'priority' => 997,
                'max_price' => 0.00,
                'billing_cycle' => 'lifetime',
                'is_active' => true,
                'group' => 'free',
            ],
            [
                'name' => 'Early Bird',
                'slug' => 'early-bird-lifetime',
                'description' => 'Special early bird offer',
                'price' => 0.00,
                'priority' => 998,
                'max_price' => 0.00,
                'billing_cycle' => 'lifetime',
                'is_active' => true,
                'group' => 'early_bird',
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter-yearly',
                'description' => 'Perfect for small teams getting started',
                'price' => 299.00,
                'priority' => 999,
                'max_price' => 349.00,
                'billing_cycle' => 'yearly',
                'is_active' => true,
                'group' => 'starter',
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro-yearly',
                'description' => 'Advanced features for growing teams',
                'price' => 499.00,
                'priority' => 1000,
                'max_price' => 599.00,
                'billing_cycle' => 'yearly',
                'is_active' => true,
                'group' => 'pro',
            ],
            [
                'name' => 'Business',
                'slug' => 'business-yearly',
                'description' => 'Complete solution for large organizations',
                'price' => 999.00,
                'priority' => 1001,
                'max_price' => 1199.00,
                'billing_cycle' => 'yearly',
                'is_active' => true,
                'group' => 'business',
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create($planData);
        }
    }
}
