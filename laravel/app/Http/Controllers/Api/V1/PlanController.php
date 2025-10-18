<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanFeature;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    /**
     * Get all available plans grouped by plan group
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get all active plans with their limits
            $plans = Plan::active()
                ->with('limits')
                ->orderBy('priority', 'desc')
                ->get();

            // Get all active features
            $allFeatures = PlanFeature::active()
                ->orderBy('category')
                ->orderBy('display_order')
                ->get();

            // Group plans by their group column and get representative plan for each group
            $groupedPlans = $plans->groupBy('group')->map(function ($groupPlans) {
                // Get the plan with highest priority (first one after ordering by priority desc)
                return $groupPlans->first();
            })->filter()->values(); // Filter out any nulls

            // Build plans object keyed by slug
            $plansData = [];
            foreach ($groupedPlans as $plan) {
                $plansData[$plan->slug] = [
                    'uuid' => $plan->uuid,
                    'group' => $plan->group,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'price' => (float) $plan->price,
                    'currency' => $plan->currency,
                    'market' => $plan->market,
                    'prices' => $plan->prices ?? null,
                    'max_price' => (float) $plan->max_price,
                    'billing_cycle' => $plan->billing_cycle,
                    'priority' => $plan->priority,
                    'is_free' => $plan->isFree(),
                ];
            }

            // Build features array (metadata only, no limits)
            $featuresData = $allFeatures->map(function ($feature) {
                return [
                    'feature' => $feature->feature,
                    'name' => $feature->name,
                    'description' => $feature->description,
                    'type' => $feature->type,
                    'category' => $feature->category,
                    'period' => $feature->period,
                ];
            })->values();

            // Build limits array keyed by group, then by feature
            $limitsData = [];
            foreach ($groupedPlans as $plan) {
                $limitsData[$plan->group] = [];
                /** @var iterable<\App\Models\PlanLimit> $planLimits */
                $planLimits = $plan->relationLoaded('limits') ? $plan->limits : [];
                foreach ($planLimits as $limit) {
                    $limitsData[$plan->group][$limit->feature] = [
                        'value' => $limit->value,
                        'type' => $limit->type,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'plans' => $plansData,
                    'features' => $featuresData,
                    'limits' => $limitsData,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching plans', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch plans. Please try again.',
            ], 500);
        }
    }
}
