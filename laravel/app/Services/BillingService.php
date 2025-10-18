<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationPlan;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\PaymentGateway\EsewaGateway;
use App\Services\PaymentGateway\KhaltiGateway;
use App\Services\PaymentGateway\MockGateway;
use App\Services\PaymentGateway\PaymentGatewayInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}
    /**
     * Get the appropriate payment gateway
     */
    public function getGateway(string $gateway): PaymentGatewayInterface
    {
        // Prevent mock gateway in production
        if ($gateway === 'mock' && app()->environment('production')) {
            throw new Exception('Mock gateway is not available in production');
        }

        return match ($gateway) {
            'esewa' => new EsewaGateway,
            'khalti' => new KhaltiGateway,
            'mock' => new MockGateway,
            default => throw new Exception('Invalid payment gateway: '.$gateway),
        };
    }

    /**
     * Initiate a payment for a plan
     *
     * @return array{success: bool, payment?: Payment, payment_url?: string, error?: string}
     */
    public function initiatePayment(
        User $user,
        Organization $organization,
        string $planSlug,
        string $gateway
    ): array {
        try {
            // Get the plan
            $plan = Plan::where('slug', $planSlug)->first();

            if (! $plan) {
                return [
                    'success' => false,
                    'error' => 'Plan not found',
                ];
            }

            // Check if plan is free
            if ($plan->isFree()) {
                return [
                    'success' => false,
                    'error' => 'Cannot process payment for free plan',
                ];
            }

            // Validate gateway supports plan currency
            if (! $this->currencyService->gatewaySupportsCurrency($gateway, $plan->currency)) {
                return [
                    'success' => false,
                    'error' => "Payment gateway {$gateway} does not support {$plan->currency}",
                ];
            }

            // Create payment record
            $payment = Payment::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'gateway' => $gateway,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'status' => 'pending',
                'metadata' => [
                    'plan_slug' => $planSlug,
                    'plan_name' => $plan->name,
                    'plan_billing_cycle' => $plan->billing_cycle,
                    'plan_market' => $plan->market,
                ],
            ]);

            // Get gateway service
            $gatewayService = $this->getGateway($gateway);

            // Initiate payment
            $result = $gatewayService->initiatePayment([
                'amount' => $plan->price,
                'order_id' => $payment->uuid,
                'product_name' => $plan->name.' - '.$plan->billing_cycle.' subscription',
                'customer_email' => $user->email,
                'customer_name' => $user->name,
            ]);

            if (! $result['success']) {
                $payment->update([
                    'status' => 'failed',
                    'notes' => $result['error'] ?? 'Payment initiation failed',
                ]);

                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Payment initiation failed',
                ];
            }

            // Update payment with gateway transaction ID
            if (isset($result['transaction_id'])) {
                $payment->update([
                    'gateway_transaction_id' => $result['transaction_id'],
                ]);
            }

            return [
                'success' => true,
                'payment' => $payment,
                'payment_url' => $result['payment_url'] ?? null,
                'form_params' => $result['form_params'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('Payment initiation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'plan_slug' => $planSlug,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to initiate payment',
            ];
        }
    }

    /**
     * Verify payment and attach plan to organization
     *
     * @return array{success: bool, organization_plan?: OrganizationPlan, error?: string}
     */
    public function verifyAndAttachPlan(Payment $payment, array $verificationData = []): array
    {
        try {
            // Check if payment is already completed
            if ($payment->status === 'completed') {
                return [
                    'success' => false,
                    'error' => 'Payment already processed',
                ];
            }

            // Get gateway service
            $gatewayService = $this->getGateway($payment->gateway);

            // For eSewa, inject total_amount and transaction_uuid from payment record
            // This is needed because eSewa may not send these in callback
            if ($payment->gateway === 'esewa') {
                $verificationData['total_amount'] = $verificationData['total_amount'] ?? $payment->amount;
                $verificationData['transaction_uuid'] = $verificationData['transaction_uuid'] ?? $payment->uuid;
            }

            // Verify payment with gateway
            $verificationResult = $gatewayService->verifyPayment(
                $payment->gateway_transaction_id ?? $payment->uuid,
                $verificationData
            );

            if (! $verificationResult['success']) {
                $payment->update([
                    'status' => 'failed',
                    'notes' => $verificationResult['error'] ?? 'Payment verification failed',
                    'gateway_response' => $verificationData,
                ]);

                return [
                    'success' => false,
                    'error' => $verificationResult['error'] ?? 'Payment verification failed',
                ];
            }

            // Update payment status
            $payment->update([
                'status' => 'completed',
                'gateway_transaction_id' => $verificationResult['transaction_id'] ?? $payment->gateway_transaction_id,
                'gateway_response' => $verificationData,
            ]);

            // Attach plan to organization
            $result = $this->attachPlanAfterPayment($payment);

            if (! $result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to attach plan',
                ];
            }

            return [
                'success' => true,
                'organization_plan' => $result['organization_plan'],
            ];

        } catch (Exception $e) {
            Log::error('Payment verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_id' => $payment->id,
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed',
            ];
        }
    }

    /**
     * Attach plan to organization after successful payment
     *
     * @return array{success: bool, organization_plan?: OrganizationPlan, error?: string}
     */
    protected function attachPlanAfterPayment(Payment $payment): array
    {
        try {
            $organization = $payment->organization;
            $planSlug = $payment->metadata['plan_slug'] ?? null;

            if (! $planSlug) {
                return [
                    'success' => false,
                    'error' => 'Plan slug not found in payment metadata',
                ];
            }

            $plan = Plan::where('slug', $planSlug)->first();

            if (! $plan) {
                return [
                    'success' => false,
                    'error' => 'Plan not found',
                ];
            }

            // Use DB transaction for atomicity
            $organizationPlan = DB::transaction(function () use ($organization, $plan, $payment) {
                // Attach plan to organization
                $orgPlan = $organization->attachPlan($plan, [
                    'user_id' => $payment->user_id,
                    'status' => 'active',
                    'started_at' => now(),
                    'ends_at' => null, // Set based on billing cycle if needed
                    'billing_cycle' => $plan->billing_cycle,
                    'charging_price' => $payment->amount,
                    'charging_currency' => $payment->currency,
                    'payment_gateway' => $payment->gateway,
                    'gateway_transaction_id' => $payment->gateway_transaction_id,
                    'metadata' => [
                        'payment_uuid' => $payment->uuid,
                        'is_paid' => true,
                    ],
                ]);

                // Update payment with organization_plan_id
                $payment->update([
                    'organization_plan_id' => $orgPlan->id,
                ]);

                return $orgPlan;
            });

            return [
                'success' => true,
                'organization_plan' => $organizationPlan,
            ];

        } catch (Exception $e) {
            Log::error('Failed to attach plan after payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_id' => $payment->id,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to attach plan',
            ];
        }
    }
}
