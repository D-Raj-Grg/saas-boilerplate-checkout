<?php

namespace App\Services\PaymentGateway;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EsewaGateway implements PaymentGatewayInterface
{
    protected string $merchantId;

    protected string $secretKey;

    protected string $apiUrl;

    protected string $verifyUrl;

    protected string $successUrl;

    protected string $failureUrl;

    public function __construct()
    {
        $this->merchantId = config('payment-gateways.esewa.merchant_id');
        $this->secretKey = config('payment-gateways.esewa.secret_key');
        $this->apiUrl = config('payment-gateways.esewa.api_url');
        $this->verifyUrl = config('payment-gateways.esewa.verify_url');
        $this->successUrl = config('payment-gateways.esewa.success_url');
        $this->failureUrl = config('payment-gateways.esewa.failure_url');
    }

    /**
     * @param  array{amount: float, order_id: string, product_name: string, customer_email?: string}  $data
     */
    public function initiatePayment(array $data): array
    {
        try {
            // eSewa uses form-based redirect, not API
            // We'll return the payment URL with parameters
            $amount = $data['amount'];
            $orderId = $data['order_id']; // This will be payment UUID
            $productName = $data['product_name'] ?? 'SaaS Subscription';

            // Build eSewa payment URL with parameters
            $paymentUrl = $this->apiUrl.'?'.http_build_query([
                'tAmt' => $amount, // Total amount
                'amt' => $amount, // Product amount
                'txAmt' => 0, // Tax amount
                'psc' => 0, // Service charge
                'pdc' => 0, // Delivery charge
                'scd' => $this->merchantId, // Merchant ID
                'pid' => $orderId, // Product ID (our payment UUID)
                'su' => $this->successUrl.'?q=su&payment_uuid='.$orderId, // Success URL
                'fu' => $this->failureUrl.'?q=fu&payment_uuid='.$orderId, // Failure URL
            ]);

            return [
                'success' => true,
                'payment_url' => $paymentUrl,
                'transaction_id' => $orderId, // eSewa transaction ID comes later
            ];
        } catch (Exception $e) {
            Log::error('eSewa payment initiation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to initiate eSewa payment',
            ];
        }
    }

    /**
     * Verify payment with eSewa
     *
     * @param  array{refId?: string, amt?: string, pid?: string}  $additionalData
     */
    public function verifyPayment(string $transactionId, array $additionalData = []): array
    {
        try {
            // eSewa verification requires: oid (order ID), refId (reference ID), amt (amount)
            $refId = $additionalData['refId'] ?? $additionalData['oid'] ?? null;
            $amount = $additionalData['amt'] ?? null;
            $productId = $additionalData['pid'] ?? $transactionId;

            if (! $refId || ! $amount) {
                return [
                    'success' => false,
                    'error' => 'Missing required verification parameters (refId or amt)',
                ];
            }

            // Make verification request to eSewa
            $response = Http::asForm()->get($this->verifyUrl, [
                'amt' => $amount,
                'scd' => $this->merchantId,
                'rid' => $refId,
                'pid' => $productId,
            ]);

            if ($response->successful()) {
                // eSewa returns XML response, check for success
                $responseBody = $response->body();

                // Simple XML parsing for success response
                if (str_contains($responseBody, '<response_code>Success</response_code>')) {
                    return [
                        'success' => true,
                        'status' => 'completed',
                        'transaction_id' => $refId,
                        'amount' => (float) $amount,
                    ];
                }
            }

            Log::warning('eSewa payment verification failed', [
                'transaction_id' => $transactionId,
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed',
            ];

        } catch (Exception $e) {
            Log::error('eSewa payment verification error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed',
            ];
        }
    }

    public function getPaymentStatus(string $transactionId): string
    {
        // eSewa doesn't have a separate status API
        // Status is determined during verification
        return 'pending';
    }

    public function refundPayment(string $transactionId, float $amount): bool
    {
        // eSewa doesn't have automatic refund API
        // Refunds must be processed manually
        Log::info('eSewa refund requested (manual processing required)', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        return false;
    }
}
