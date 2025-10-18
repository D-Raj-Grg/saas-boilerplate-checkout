<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Http\Requests\Payment\VerifyPaymentRequest;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Services\BillingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Initiate a payment for a plan (supports guest checkout)
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse|Response
    {
        try {
            $user = Auth::guard('sanctum')->user();

            // Handle guest checkout
            if (! $user instanceof User) {
                // Validate guest information is provided
                if (! $request->has('guest_name') || ! $request->has('guest_email')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Name and email are required for guest checkout',
                    ], 400);
                }

                // Check if user already exists with this email
                $existingUser = User::where('email', $request->input('guest_email'))->first();

                if ($existingUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An account with this email already exists. Please login to continue.',
                    ], 400);
                }

                // Create guest user account
                $user = $this->createGuestUser(
                    $request->input('guest_name'),
                    $request->input('guest_email')
                );

                // Create default organization for guest user
                $organization = $this->createDefaultOrganization($user);

                Log::info('Guest user created for checkout', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'organization_id' => $organization->id,
                ]);
            } else {
                // Authenticated user flow
                $organization = $user->currentOrganization;

                if (! $organization) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No active organization found. Please create or select an organization.',
                    ], 400);
                }
            }

            $planSlug = $request->input('plan_slug');
            $gateway = $request->input('gateway');

            // Initiate payment
            $result = $this->billingService->initiatePayment(
                $user,
                $organization,
                $planSlug,
                $gateway
            );

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Failed to initiate payment',
                ], 400);
            }

            // Build response data
            $responseData = [
                'payment_uuid' => $result['payment']->uuid,
                'payment_url' => $result['payment_url'],
                'gateway' => $gateway,
            ];

            // For eSewa, include form parameters for frontend to build the form
            if ($gateway === 'esewa' && isset($result['form_params'])) {
                $responseData['form_params'] = $result['form_params'];
            }

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ]);

        } catch (Exception $e) {
            Log::error('Payment initiation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'guest_email' => $request->input('guest_email'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify payment and attach plan (supports guest verification)
     */
    public function verify(Payment $payment, VerifyPaymentRequest $request): JsonResponse
    {
        try {
            // Verify user has access to this payment (optional for guests)
            $user = Auth::guard('sanctum')->user();

            // If user is authenticated, verify they own the payment
            if ($user instanceof User && $payment->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to payment',
                ], 403);
            }

            // Get verification data from request
            $verificationData = $request->only([
                // eSewa v1 (legacy)
                'oid', 'refId', 'amt', 'pid',
                // eSewa v2
                'data', 'transaction_uuid', 'total_amount',
                // Khalti
                'pidx', 'transaction_id', 'status',
                // Generic
                'q', 'payment_uuid',
            ]);

            // Verify payment and attach plan
            $result = $this->billingService->verifyAndAttachPlan($payment, $verificationData);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Payment verification failed',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'payment_status' => 'completed',
                    'plan_attached' => true,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Payment verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_id' => $payment->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please contact support.',
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function status(Payment $payment, Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (! $user instanceof User || $payment->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to payment',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_uuid' => $payment->uuid,
                    'status' => $payment->status,
                    'gateway' => $payment->gateway,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'created_at' => $payment->created_at->toIso8601String(),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get payment status', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment status',
            ], 500);
        }
    }

    /**
     * Get payment history for current organization
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (! $user instanceof User) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $organization = $user->currentOrganization;

            if (! $organization) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active organization found',
                ], 400);
            }

            $payments = Payment::where('organization_id', $organization->id)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $payments->map(function ($payment) {
                    return [
                        'uuid' => $payment->uuid,
                        'gateway' => $payment->gateway,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'plan_name' => $payment->metadata['plan_name'] ?? 'N/A',
                        'created_at' => $payment->created_at->toIso8601String(),
                    ];
                }),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get payment history', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment history',
            ], 500);
        }
    }

    /**
     * Refund a payment (admin only)
     */
    public function refund(Payment $payment, Request $request): JsonResponse
    {
        try {
            // TODO: Add admin authorization check

            if ($payment->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed payments can be refunded',
                ], 400);
            }

            // Get gateway and attempt refund
            $gateway = $this->billingService->getGateway($payment->gateway);
            $refunded = $gateway->refundPayment(
                $payment->gateway_transaction_id ?? $payment->uuid,
                $payment->amount
            );

            if ($refunded) {
                $payment->update(['status' => 'refunded']);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment refunded successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Refund not supported for this gateway. Please process manually.',
            ], 400);

        } catch (Exception $e) {
            Log::error('Payment refund failed', [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund',
            ], 500);
        }
    }

    /**
     * Create a guest user account
     */
    private function createGuestUser(string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)), // Random password
            'email_verified_at' => now(), // Auto-verify for guest users who complete payment
        ]);
    }

    /**
     * Create default organization for a guest user
     */
    private function createDefaultOrganization(User $user): Organization
    {
        $organization = Organization::create([
            'name' => $user->name."'s Organization",
            'user_id' => $user->id,
            'owner_id' => $user->id,
        ]);

        // Attach user as owner
        $organization->users()->attach($user->id, ['role' => OrganizationRole::OWNER->value]);

        // Set as current organization (using ID, not UUID)
        $user->update(['current_organization_id' => $organization->id]);

        return $organization;
    }
}
