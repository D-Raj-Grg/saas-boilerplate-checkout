<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define core SaaS features
        // Users can extend this with their own custom features as needed
        $features = [
            // Organization & Workspace
            [
                'feature' => 'team_members',
                'name' => 'Team Members',
                'description' => 'Number of team members per organization',
                'type' => 'limit',
                'period' => 'lifetime',
                'category' => 'team',
                'tracking_scope' => 'organization',
                'display_order' => 1,
            ],
            [
                'feature' => 'workspaces',
                'name' => 'Workspaces',
                'description' => 'Number of workspaces per organization',
                'type' => 'limit',
                'period' => 'lifetime',
                'category' => 'organization',
                'tracking_scope' => 'organization',
                'display_order' => 2,
            ],
            [
                'feature' => 'connections_per_workspace',
                'name' => 'Connections per Workspace',
                'description' => 'Number of external service connections per workspace',
                'type' => 'limit',
                'period' => 'lifetime',
                'category' => 'connections',
                'tracking_scope' => 'workspace',
                'display_order' => 3,
            ],

            // API & Performance
            [
                'feature' => 'api_rate_limit',
                'name' => 'API Rate Limit',
                'description' => 'API requests per minute',
                'type' => 'limit',
                'period' => 'lifetime',
                'category' => 'api',
                'tracking_scope' => 'organization',
                'display_order' => 4,
            ],
            [
                'feature' => 'unique_visitors',
                'name' => 'Monthly Active Users',
                'description' => 'Number of monthly active users',
                'type' => 'limit',
                'period' => 'monthly',
                'category' => 'usage',
                'tracking_scope' => 'organization',
                'display_order' => 5,
            ],
            [
                'feature' => 'data_retention_days',
                'name' => 'Data Retention',
                'description' => 'Days of data retention',
                'type' => 'limit',
                'period' => 'lifetime',
                'category' => 'storage',
                'tracking_scope' => 'organization',
                'display_order' => 6,
            ],

            // Support & Features
            [
                'feature' => 'priority_support',
                'name' => 'Priority Support',
                'description' => 'Access to priority customer support',
                'type' => 'boolean',
                'period' => 'lifetime',
                'category' => 'support',
                'tracking_scope' => 'organization',
                'display_order' => 7,
            ],
        ];

        // Insert features (tables are truncated by PlanSeeder)
        foreach ($features as $feature) {
            DB::table('plan_features')->insert(
                array_merge($feature, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Define plan limits for each plan
        $plans = DB::table('plans')->get();

        $planLimits = [
            'free' => [
                'team_members' => '5',
                'workspaces' => '1',
                'connections_per_workspace' => '1',
                'api_rate_limit' => '60',
                'unique_visitors' => '1000',
                'data_retention_days' => '7',
                'priority_support' => 'false',
            ],
            'early-bird-lifetime' => [
                'team_members' => '-1',
                'workspaces' => '-1',
                'connections_per_workspace' => '-1',
                'api_rate_limit' => '600',
                'unique_visitors' => '100000',
                'data_retention_days' => '90',
                'priority_support' => 'true',
            ],
            'starter-yearly' => [
                'team_members' => '10',
                'workspaces' => '3',
                'connections_per_workspace' => '3',
                'api_rate_limit' => '120',
                'unique_visitors' => '10000',
                'data_retention_days' => '30',
                'priority_support' => 'false',
            ],
            'pro-yearly' => [
                'team_members' => '50',
                'workspaces' => '10',
                'connections_per_workspace' => '10',
                'api_rate_limit' => '300',
                'unique_visitors' => '50000',
                'data_retention_days' => '90',
                'priority_support' => 'true',
            ],
            'business-yearly' => [
                'team_members' => '-1',
                'workspaces' => '-1',
                'connections_per_workspace' => '-1',
                'api_rate_limit' => '600',
                'unique_visitors' => '-1',
                'data_retention_days' => '365',
                'priority_support' => 'true',
            ],
        ];

        // Insert or update plan limits with type and tracking_scope information
        foreach ($plans as $plan) {
            $slug = $plan->slug;
            if (isset($planLimits[$slug])) {
                foreach ($planLimits[$slug] as $feature => $value) {
                    // Get feature type and tracking_scope from the features array
                    $featureConfig = collect($features)->firstWhere('feature', $feature);
                    $featureType = $featureConfig['type'] ?? 'limit';
                    $trackingScope = $featureConfig['tracking_scope'] ?? 'organization';

                    DB::table('plan_limits')->insert([
                        'plan_id' => $plan->id,
                        'feature' => $feature,
                        'value' => $value,
                        'type' => $featureType,
                        'tracking_scope' => $trackingScope,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
