<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\GetCheckoutUrlRequest;
use App\Jobs\ProcessSureCartWebhook;
use App\Models\Organization;
use App\Models\OrganizationPlan;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use App\Services\SureCartService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function __construct(
        protected SureCartService $sureCartService
    ) {}

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

            // Get price mapping from config
            $priceIds = config('services.surecart.price_mapping', []);

            // Check if user is authenticated and has an active subscription (optional auth)
            $user = Auth::guard('sanctum')->user();
            $activeSubscription = null;

            if ($user instanceof User) {
                $currentOrganization = $user->currentOrganization;
                if ($currentOrganization instanceof Organization) {
                    $activeSubscription = OrganizationPlan::where('organization_id', $currentOrganization->id)
                        ->where('status', 'active')
                        ->where('is_revoked', false)
                        ->first();
                }
            }

            // Build checkout URLs
            $checkoutUrls = [];
            $upgradeDowngradeUrls = [];

            foreach ($groupedPlans as $plan) {
                $slug = $plan->slug;

                // Standard checkout URL for new purchases
                if ($slug === 'free') {
                    $checkoutUrls[$slug] = '/registration';
                } elseif (isset($priceIds[$slug])) {
                    $priceIdSlug = strval($priceIds[$slug]);
                    $checkoutUrls[$slug] = "/checkout/?line_items%5B0%5D%5Bprice_id%5D={$priceIdSlug}&line_items%5B0%5D%5Bquantity%5D=1";
                }

                // Upgrade/downgrade URL for users with active subscription
                if ($activeSubscription && isset($activeSubscription->metadata['subscription_id']) && isset($priceIds[$slug])) {
                    $subscriptionId = $activeSubscription->metadata['subscription_id'];
                    $upgradeDowngradeUrls[$slug] = "/customer-dashboard/?action=confirm&model=subscription&id={$subscriptionId}&price_id=".$priceIds[$slug];
                }
            }

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
                    'max_price' => (float) $plan->max_price,
                    'billing_cycle' => $plan->billing_cycle,
                    'priority' => $plan->priority,
                    'checkout_url' => $checkoutUrls[$plan->slug] ?? null,
                    'upgrade_downgrade_url' => $upgradeDowngradeUrls[$plan->slug] ?? null,
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

    public function webhook(Request $request): JsonResponse
    {
        $webhookId = strval($request->get('id'));

        try {
            // Verify webhook signature
            $signingSecret = strval(config('services.surecart.signing_secret'));
            $webhookSignature = $request->header('x-webhook-signature');
            $webhookTimestamp = $request->header('x-webhook-timestamp');
            $payload = $webhookTimestamp.'.'.$request->getContent();
            $signedHash = hash_hmac('sha256', $payload, $signingSecret);

            if ($signedHash !== $webhookSignature && config('app.env') !== 'local') {
                Log::warning('Invalid webhook signature', ['webhook_id' => $webhookId]);

                return response()->json(['message' => 'Invalid Request'], 401);
            }

            // Check for duplicate webhooks
            if (OrganizationPlan::whereJsonContains('metadata->webhook_ids', $webhookId)->exists()) {
                Log::info('Duplicate webhook received', ['webhook_id' => $webhookId]);

                return response()->json(['message' => 'Already processed'], 200);
            }

            // Dispatch webhook processing to queue
            if ($request->get('object') === 'event') {
                $type = $request->get('type');
                $data = $request->get('data');

                if (isset($data['object'])) {
                    ProcessSureCartWebhook::dispatch($type, $data['object'], $webhookId)
                        ->onQueue('webhooks');
                }
            }

            return response()->json(['success' => true], 200);
        } catch (Exception $e) {
            Log::error('SureCart Webhook Error', [
                'error' => $e->getMessage(),
                'error_line_number' => $e->getLine(),
                'error_file_name' => $e->getFile(),
                'webhook_id' => $webhookId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Get checkout URL for a plan
     */
    public function getCheckoutUrl(GetCheckoutUrlRequest $request): JsonResponse
    {
        try {
            $user = Auth::guard('sanctum')->user();

            $planSlug = $request->input('plan_slug');
            $source = $request->input('source', 'pricing');
            $coupon = $request->input('coupon');
            $aff = $request->input('aff');
            $checkoutUrl = $request->input('checkout_url');
            // Build the final checkout URL with login redirect
            $billingStoreUrl = config('billing-store.url');
            $loginUrl = rtrim($billingStoreUrl, '/').'/surecart/redirect/';
            $finalUrl = '';

            if (! $user instanceof User) {
                $checkoutUrl = ltrim($checkoutUrl, '/');

                if ($coupon) {
                    $checkoutUrl .= '&coupon='.urlencode($coupon);
                }
                if ($aff) {
                    $checkoutUrl .= '&aff='.urlencode($aff);
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'checkout_url' => rtrim($billingStoreUrl, '/').'/'.$checkoutUrl,
                    ],
                ]);
            }

            if (Str::contains($checkoutUrl, 'customer-dashboard')) {
                $loginId = $this->sureCartService->getLoginLink($user);
                if (! $loginId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to create checkout link. Please try again.',
                    ], 500);
                }
                $finalUrl = $loginUrl.'?customer_link_id='.$loginId.'&path='.urlencode($checkoutUrl);
            } else {
                // Create login link and checkout URL
                $result = $this->sureCartService->createLoginLinkAndCheckoutUrl(
                    $user,
                    $planSlug,
                    $source
                );

                if (! $result) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to create checkout link. Please try again.',
                    ], 500);
                }
                $checkoutUrl = $result['checkout_url'];

                // Add coupon and affiliate parameters if provided
                if ($coupon) {
                    $checkoutUrl .= '&coupon='.urlencode($coupon);
                }

                if ($aff) {
                    $checkoutUrl .= '&aff='.urlencode($aff);
                }
                $finalUrl = $loginUrl.'?customer_link_id='.$result['login_id'].'&path='.urlencode($checkoutUrl);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'checkout_url' => $finalUrl,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Error generating checkout URL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create checkout link. Please try again.',
            ], 500);
        }
    }

    /**
     * Get customer dashboard URL
     */
    public function getCustomerDashboardUrl(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user instanceof User) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            // Create login link and checkout URL
            $loginId = $this->sureCartService->getLoginLink(
                $user
            );

            if (! $loginId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to create customer dashboard link. Please try again.',
                ], 500);
            }

            // Build the final checkout URL with login redirect
            $billingStoreUrl = config('billing-store.url');
            $loginUrl = rtrim($billingStoreUrl, '/').'/surecart/redirect/';
            $finalUrl = $loginUrl.'?customer_link_id='.$loginId.'&path='.urlencode('/customer-dashboard');

            return response()->json([
                'success' => true,
                'data' => [
                    'customer_dashboard_url' => $finalUrl,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Error generating customer dashboard URL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create customer dashboard link. Please try again.',
            ], 500);
        }
    }
}
