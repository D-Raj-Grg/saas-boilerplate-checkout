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
            // Nepal Market Plans (NPR)
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Free plan with basic features',
                'price' => 0.00,
                'currency' => 'NPR',
                'market' => 'nepal',
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
                'currency' => 'NPR',
                'market' => 'nepal',
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
                'currency' => 'NPR',
                'market' => 'nepal',
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
                'currency' => 'NPR',
                'market' => 'nepal',
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
                'currency' => 'NPR',
                'market' => 'nepal',
                'priority' => 1001,
                'max_price' => 1199.00,
                'billing_cycle' => 'yearly',
                'is_active' => true,
                'group' => 'business',
            ],
            // International Market Plans (USD) - For future Stripe integration
            // Uncomment when ready to offer international plans
            /*
            [
                'name' => 'Starter',
                'slug' => 'starter-yearly-usd',
                'description' => 'Perfect for small teams getting started',
                'price' => 9.00,
                'currency' => 'USD',
                'market' => 'international',
                'prices' => json_encode(['USD' => 9.00, 'EUR' => 8.50, 'GBP' => 7.50]),
                'priority' => 999,
                'max_price' => 12.00,
                'billing_cycle' => 'yearly',
                'is_active' => false, // Activate when Stripe is ready
                'group' => 'starter',
            ],
            */
        ];

        foreach ($plans as $planData) {
            Plan::create($planData);
        }
    }
}
