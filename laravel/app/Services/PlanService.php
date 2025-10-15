<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Jobs\SendWelcomeEmailJob;
use App\Models\Organization;
use App\Models\OrganizationPlan;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlanService
{
    /**
     * @param  array<string, mixed>  $purchaseData
     */
    public function purchaseCreated(array $purchaseData, string $webhookId): void
    {
        try {
            $purchaseId = $purchaseData['id'];
            $expandedData = $this->expandPurchaseData($purchaseId);

            if (! $expandedData) {
                Log::error('Failed to expand purchase data', ['purchase_id' => $purchaseId]);

                return;
            }

            // \Log::info(print_r($expandedData, true));

            $organization = $this->getOrganizationFromPurchase($expandedData);
            if (! $organization) {
                Log::error('Organization not found for purchase', ['purchase_id' => $purchaseId]);

                return;
            }

            $priceId = strval($expandedData['price']['id'] ?? '');
            $plan = $this->getPlanFromPriceId($priceId);

            if (! $plan) {
                Log::error('Plan not found for price ID', ['price_id' => $priceId]);

                return;
            }

            $subscription = $expandedData['subscription'] ?? null;
            $checkoutIds = $this->extractCheckoutIds($expandedData);

            $startDate = isset($expandedData['created_at'])
                ? Carbon::createFromTimestamp($expandedData['created_at'])
                : now();

            $trialStartDate = isset($subscription['trial_start_at'])
                ? Carbon::createFromTimestamp($subscription['trial_start_at'])
                : null;

            $trialEndDate = isset($subscription['trial_end_at'])
                ? Carbon::createFromTimestamp($subscription['trial_end_at'])
                : null;

            DB::transaction(function () use ($organization, $plan, $expandedData, $subscription, $checkoutIds, $startDate, $trialStartDate, $trialEndDate, $purchaseId, $webhookId, $priceId) {
                // Use the single source of truth method
                $organization->attachPlan($plan, [
                    'purchase_uuid' => $purchaseId,
                    'checkout_uuid' => json_encode($checkoutIds),
                    'sc_price_uuid' => $priceId,
                    'trial_start' => $trialStartDate,
                    'trial_end' => $trialEndDate,
                    'started_at' => $startDate,
                    'ends_at' => null,
                    'status' => $subscription['status'] ?? 'active',
                    'is_revoked' => $expandedData['revoked'] ?? false,
                    'revoked_at' => isset($expandedData['revoked_at'])
                        ? Carbon::createFromTimestamp($expandedData['revoked_at'])
                        : null,
                    'billing_cycle' => $this->detectBillingCycle($subscription),
                    'charging_price' => $expandedData['price']['amount'] ?? 0,
                    'charging_currency' => $expandedData['price']['currency'] ?? 'usd',
                    'metadata' => [
                        'webhook_ids' => [$webhookId],
                        'customer_id' => strval($expandedData['customer']['id'] ?? ''),
                        'subscription_id' => $subscription['id'] ?? '',
                        'customer_email' => strval($expandedData['customer']['email'] ?? ''),
                        'is_paid' => true,
                    ],
                ]);
            });

            $freePlan = Plan::where('slug', 'free')->first();

            if ($freePlan && ! $this->isTopUpPlan($plan)) {
                $existingFreePlan = $organization->organizationPlans()
                    ->where('plan_id', $freePlan->id)
                    ->where('is_revoked', false)
                    ->first();

                if ($existingFreePlan) {
                    $existingFreePlan->update([
                        'status' => 'cancelled',
                        'ends_at' => now(),
                        'notes' => 'Replaced by new purchase',
                    ]);
                }
            }

            $this->linkCustomerToUser(
                strval($expandedData['customer']['id'] ?? ''),
                strval($expandedData['customer']['email'] ?? '')
            );

        } catch (Exception $e) {
            Log::error('Error in purchaseCreated', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'webhook_id' => $webhookId,
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $purchaseData
     */
    public function purchaseRevoked(array $purchaseData, string $webhookId): void
    {
        try {
            $purchaseId = $purchaseData['id'];
            $expandedData = $this->expandPurchaseData($purchaseId);

            if (! $expandedData) {
                Log::error('Failed to expand purchase data', ['purchase_id' => $purchaseId]);

                return;
            }

            $organizationPlan = OrganizationPlan::where('purchase_uuid', $purchaseId)->first();

            if (! $organizationPlan) {
                Log::error('Organization plan not found for purchase', ['purchase_id' => $purchaseId]);

                return;
            }

            DB::transaction(function () use ($organizationPlan, $expandedData, $webhookId) {
                $revokedReason = $expandedData['revoked_reason'] ?? 'User cancelled';
                $metadata = $organizationPlan->metadata ?? [];
                $webhookIds = $metadata['webhook_ids'] ?? [];
                $webhookIds[] = $webhookId;

                $organizationPlan->update([
                    'status' => 'cancelled',
                    'ends_at' => now(),
                    'notes' => $revokedReason,
                    'is_revoked' => true,
                    'revoked_at' => isset($expandedData['revoked_at'])
                        ? Carbon::createFromTimestamp($expandedData['revoked_at'])
                        : now(),
                    'metadata' => array_merge($metadata, [
                        'webhook_ids' => $webhookIds,
                        'revoked_reason' => $revokedReason,
                    ]),
                ]);

                $organization = $organizationPlan->organization;

                if (! $organization) {
                    Log::error('Organization not found for plan', ['organization_plan_id' => $organizationPlan->id]);

                    return;
                }

                // Check if org needs free plan
                $hasActivePlan = $organization->organizationPlans()
                    ->where('status', 'active')
                    ->where('is_revoked', false)
                    ->exists();

                if (! $hasActivePlan) {
                    Log::info('Organization has no active plans after cancellation', [
                        'organization_id' => $organization->id,
                        'organization_name' => $organization->name,
                    ]);

                    // Optionally assign free plan using the single source of truth method
                    // $organization->attachPlan('free', [
                    //     'metadata' => [
                    //         'webhook_ids' => [$webhookId],
                    //         'is_paid' => false,
                    //         'auto_assigned' => true,
                    //     ],
                    // ]);
                }
            });

        } catch (Exception $e) {
            Log::error('Error in purchaseRevoked', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'webhook_id' => $webhookId,
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $purchaseData
     */
    public function purchaseInvoked(array $purchaseData, string $webhookId): void
    {
        try {
            $purchaseId = $purchaseData['id'];
            $expandedData = $this->expandPurchaseData($purchaseId);

            if (! $expandedData) {
                Log::error('Failed to expand purchase data', ['purchase_id' => $purchaseId]);

                return;
            }

            $organizationPlan = OrganizationPlan::where('purchase_uuid', $purchaseId)->first();

            if (! $organizationPlan) {
                Log::error('Organization plan not found for purchase', ['purchase_id' => $purchaseId]);

                return;
            }

            DB::transaction(function () use ($organizationPlan, $expandedData, $webhookId) {
                $subscription = $expandedData['subscription'] ?? null;
                $metadata = $organizationPlan->metadata ?? [];
                $webhookIds = $metadata['webhook_ids'] ?? [];
                $webhookIds[] = $webhookId;

                $organizationPlan->update([
                    'is_revoked' => false,
                    'revoked_at' => null,
                    'revoked_by' => null,
                    'status' => $subscription['status'] ?? 'active',
                    'ends_at' => null,
                    'metadata' => array_merge($metadata, [
                        'webhook_ids' => $webhookIds,
                        'invoked_at' => now()->toIso8601String(),
                    ]),
                ]);

                $organization = $organizationPlan->organization;
                $plan = $organizationPlan->plan;

                if (! $organization) {
                    Log::error('Organization not found for plan', ['organization_plan_id' => $organizationPlan->id]);

                    return;
                }

                $freePlan = Plan::where('slug', 'free')->first();

                if ($freePlan && $plan && ! $this->isTopUpPlan($plan)) {
                    $existingFreePlan = $organization->organizationPlans()
                        ->where('plan_id', $freePlan->id)
                        ->where('is_revoked', false)
                        ->first();

                    if ($existingFreePlan) {
                        $existingFreePlan->update([
                            'status' => 'cancelled',
                            'ends_at' => now(),
                            'notes' => 'Replaced by invoked purchase',
                        ]);
                    }
                }
            });

        } catch (Exception $e) {
            Log::error('Error in purchaseInvoked', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'webhook_id' => $webhookId,
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $purchaseData
     */
    public function purchaseUpdated(array $purchaseData, string $webhookId): void
    {
        try {
            $purchaseId = $purchaseData['id'];
            $expandedData = $this->expandPurchaseData($purchaseId);

            if (! $expandedData) {
                Log::error('Failed to expand purchase data', ['purchase_id' => $purchaseId]);

                return;
            }

            $organizationPlan = OrganizationPlan::where('purchase_uuid', $purchaseId)->first();

            if (! $organizationPlan) {
                $this->purchaseCreated($purchaseData, $webhookId);

                return;
            }

            $priceId = strval($expandedData['price']['id'] ?? '');
            $newPlan = $this->getPlanFromPriceId($priceId);

            if (! $newPlan) {
                Log::error('Plan not found for price ID', ['price_id' => $priceId]);

                return;
            }

            DB::transaction(function () use ($organizationPlan, $expandedData, $newPlan, $webhookId, $priceId) {
                $subscription = $expandedData['subscription'] ?? null;
                $checkoutIds = $this->extractCheckoutIds($expandedData);

                $trialStartDate = isset($subscription['trial_start_at'])
                    ? Carbon::createFromTimestamp($subscription['trial_start_at'])
                    : $organizationPlan->trial_start;

                $trialEndDate = isset($subscription['trial_end_at'])
                    ? Carbon::createFromTimestamp($subscription['trial_end_at'])
                    : $organizationPlan->trial_end;

                $metadata = $organizationPlan->metadata ?? [];
                $webhookIds = $metadata['webhook_ids'] ?? [];
                $webhookIds[] = $webhookId;

                $organizationPlan->update([
                    'plan_id' => $newPlan->id,
                    'sc_price_uuid' => $priceId,
                    'checkout_uuid' => json_encode($checkoutIds),
                    'trial_start' => $trialStartDate,
                    'trial_end' => $trialEndDate,
                    'status' => $subscription['status'] ?? 'active',
                    'is_revoked' => $expandedData['revoked'] ?? false,
                    'billing_cycle' => $this->detectBillingCycle($subscription),
                    'charging_price' => $expandedData['price']['amount'] ?? 0,
                    'charging_currency' => $expandedData['price']['currency'] ?? 'usd',
                    'metadata' => array_merge($metadata, [
                        'webhook_ids' => $webhookIds,
                        'updated_at' => now()->toIso8601String(),
                        'customer_id' => strval($expandedData['customer']['id'] ?? ''),
                        'subscription_id' => $subscription['id'] ?? '',
                    ]),
                ]);
            });

        } catch (Exception $e) {
            Log::error('Error in purchaseUpdated', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'webhook_id' => $webhookId,
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $subscriptionData
     */
    public function subscriptionRenewed(array $subscriptionData, string $webhookId): void
    {
        try {
            $purchaseId = $subscriptionData['purchase'];
            $expandedData = $this->expandPurchaseData($purchaseId);

            if (! $expandedData) {
                Log::error('Failed to expand purchase data', ['purchase_id' => $purchaseId]);

                return;
            }

            $organizationPlan = OrganizationPlan::where('purchase_uuid', $purchaseId)->first();

            if (! $organizationPlan) {
                Log::error('Organization plan not found for purchase', ['purchase_id' => $purchaseId]);

                return;
            }

            DB::transaction(function () use ($organizationPlan, $expandedData, $webhookId) {
                $subscription = $expandedData['subscription'] ?? null;
                $checkoutIds = $this->extractCheckoutIds($expandedData);

                $startDate = isset($subscription['current_period_start_at'])
                    ? Carbon::createFromTimestamp($subscription['current_period_start_at'])
                    : now();

                $endDate = isset($subscription['current_period_end_at'])
                    ? Carbon::createFromTimestamp($subscription['current_period_end_at'])
                    : null;

                $metadata = $organizationPlan->metadata ?? [];
                $webhookIds = $metadata['webhook_ids'] ?? [];
                $webhookIds[] = $webhookId;

                $organizationPlan->update([
                    'started_at' => $startDate,
                    'ends_at' => $endDate,
                    'checkout_uuid' => json_encode($checkoutIds),
                    'status' => $subscription['status'] ?? 'active',
                    'metadata' => array_merge($metadata, [
                        'webhook_ids' => $webhookIds,
                        'renewed_at' => now()->toIso8601String(),
                        'period_start' => $startDate->toIso8601String(),
                        'period_end' => $endDate ? $endDate->toIso8601String() : null,
                    ]),
                ]);
            });

        } catch (Exception $e) {
            Log::error('Error in subscriptionRenewed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'webhook_id' => $webhookId,
            ]);
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function expandPurchaseData(string $purchaseId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.config('services.surecart.api_key'),
            ])->get(
                "https://api.surecart.com/v1/purchases/{$purchaseId}?expand[]=subscription&expand[]=subscription.period&expand[]=customer&expand[]=initial_order&expand[]=order.checkout&expand[]=product&expand[]=price"
            );

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to expand purchase data', [
                'purchase_id' => $purchaseId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Exception expanding purchase data', [
                'purchase_id' => $purchaseId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $purchaseData
     */
    protected function getOrganizationFromPurchase(array $purchaseData): ?Organization
    {
        $existingPlan = OrganizationPlan::where('purchase_uuid', $purchaseData['id'])->first();
        if ($existingPlan) {
            return $existingPlan->organization;
        }

        $organizationUuid = $purchaseData['initial_order']['checkout']['metadata']['organization_id'] ?? null;
        if ($organizationUuid) {
            $organization = Organization::where('uuid', $organizationUuid)->first();
            if ($organization) {
                return $organization;
            }
        }

        $customerEmail = $purchaseData['customer']['email'] ?? null;
        if ($customerEmail) {
            // Use firstOrCreate to prevent race conditions
            $userData = [
                'first_name' => $purchaseData['customer']['first_name'] ?? '',
                'last_name' => $purchaseData['customer']['last_name'] ?? '',
                'name' => trim(($purchaseData['customer']['first_name'] ?? '').' '.($purchaseData['customer']['last_name'] ?? '')),
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ];

            $user = User::firstOrCreate(
                ['email' => $customerEmail],
                $userData
            );

            $isNewUser = $user->wasRecentlyCreated;

            // First, try to find a free organization to upgrade
            $organization = $user->ownedOrganizations()
                ->whereHas('plans', function ($query) {
                    $query->where('slug', 'free')
                        ->where('status', 'active')
                        ->where('is_revoked', false);
                })
                ->first();

            // If no free org exists, create a new organization for this paid plan
            if (! $organization) {
                // Use DB transaction to prevent race conditions
                $organization = DB::transaction(function () use ($user) {
                    $organization = Organization::create([
                        'name' => $user->name."'s Organization",
                        'owner_id' => $user->id,
                    ]);

                    // Create default workspace for the organization
                    $workspace = Workspace::create([
                        'organization_id' => $organization->id,
                        'name' => 'Default Workspace',
                    ]);

                    $user->organizations()->attach($organization, [
                        'role' => OrganizationRole::OWNER->value,
                        'joined_at' => now(),
                    ]);

                    // Attach user to workspace
                    $workspace->users()->attach($user->id, [
                        'role' => WorkspaceRole::MANAGER->value,
                        'joined_at' => now(),
                    ]);

                    // Set user's current organization and workspace if not already set
                    if (! $user->current_organization_id) {
                        $user->update([
                            'current_organization_id' => $organization->id,
                            'current_workspace_id' => $workspace->id,
                        ]);
                    }

                    return $organization;
                });
            }

            if ($isNewUser) {
                SendWelcomeEmailJob::dispatch($user);
            }

            return $organization;
        }

        return null;
    }

    protected function getPlanFromPriceId(string $priceId): ?Plan
    {

        $priceMapping = array_flip(config('services.surecart.price_mapping', []));
        $planSlug = $priceMapping[$priceId] ?? null;

        if ($planSlug) {
            $plan = Plan::where('slug', $planSlug)->first();

            return $plan;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $expandedData
     * @return array<int, string>
     */
    protected function extractCheckoutIds(array $expandedData): array
    {
        $checkoutIds = [];

        $subscription = $expandedData['subscription'] ?? null;
        if ($subscription && isset($subscription['periods']['data'])) {
            $checkoutIds = Arr::pluck($subscription['periods']['data'], 'checkout');
        }

        if (empty($checkoutIds) && isset($expandedData['initial_order']['checkout']['id'])) {
            $checkoutIds[] = strval($expandedData['initial_order']['checkout']['id']);
        }

        return array_filter($checkoutIds);
    }

    /**
     * @param  array<string, mixed>|null  $subscription
     */
    protected function detectBillingCycle(?array $subscription): ?string
    {
        return 'yearly';
    }

    protected function linkCustomerToUser(string $customerId, string $customerEmail): void
    {
        if (! $customerId || ! $customerEmail) {
            return;
        }

        $user = User::where('email', $customerEmail)->first();
        if (! $user) {
            return;
        }

        /** @var array<string, mixed> $existingMeta */
        $existingMeta = $user->metadata ?? [];

        /** @var array<string, string> $scCustomerIds */
        $scCustomerIds = isset($existingMeta['sc_customer_ids']) && is_array($existingMeta['sc_customer_ids'])
            ? $existingMeta['sc_customer_ids']
            : [];

        $environment = config('app.env') === 'production' ? 'live' : 'test';
        $scCustomerIds[$environment] = $customerId;

        $existingMeta['sc_customer_ids'] = $scCustomerIds;
        $user->update(['metadata' => $existingMeta]);
    }

    protected function isTopUpPlan(Plan $plan): bool
    {
        return str_contains(strtolower($plan->slug), 'topup') ||
               str_contains(strtolower($plan->name), 'top-up');
    }
}
