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
            // eSewa v2 API uses form-based POST redirect with HMAC signature
            $amount = $data['amount'];
            $orderId = $data['order_id']; // This will be payment UUID
            $productName = $data['product_name'] ?? 'SaaS Subscription';

            // eSewa v2 parameters
            $taxAmount = 0;
            $serviceCharge = 0;
            $deliveryCharge = 0;
            $totalAmount = $amount + $taxAmount + $serviceCharge + $deliveryCharge;

            // Generate HMAC-SHA256 signature
            // Format: "total_amount={amount},transaction_uuid={uuid},product_code={code}"
            $message = "total_amount={$totalAmount},transaction_uuid={$orderId},product_code={$this->merchantId}";
            $signature = base64_encode(hash_hmac('sha256', $message, $this->secretKey, true));

            // Return form parameters as JSON for frontend to build and submit
            // This provides better UX control on the frontend
            // Note: eSewa returns transaction_uuid in the 'data' parameter, so we don't append it to URLs
            $formParams = [
                'amount' => (float) $amount,
                'tax_amount' => (float) $taxAmount,
                'total_amount' => (float) $totalAmount,
                'transaction_uuid' => $orderId,
                'product_code' => $this->merchantId,
                'product_service_charge' => (float) $serviceCharge,
                'product_delivery_charge' => (float) $deliveryCharge,
                'success_url' => $this->successUrl,
                'failure_url' => $this->failureUrl,
                'signed_field_names' => 'total_amount,transaction_uuid,product_code',
                'signature' => $signature,
            ];

            return [
                'success' => true,
                'payment_url' => $this->apiUrl, // eSewa form action URL
                'form_params' => $formParams, // Form parameters for frontend
                'transaction_id' => $orderId, // eSewa ref_id comes after payment
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
     * Verify payment with eSewa v2 API
     *
     * @param  array{total_amount?: string, transaction_uuid?: string, data?: string, refId?: string}  $additionalData
     */
    public function verifyPayment(string $transactionId, array $additionalData = []): array
    {
        try {
            // eSewa v2 verification requires: product_code, total_amount, transaction_uuid
            // The transaction_uuid should be our payment UUID
            $transactionUuid = $additionalData['transaction_uuid'] ?? $transactionId;
            $totalAmount = $additionalData['total_amount'] ?? null;

            // eSewa v2 may also return Base64-encoded 'data' parameter
            // Decode it if present to extract transaction details
            if (isset($additionalData['data'])) {
                $decodedData = json_decode(base64_decode($additionalData['data']), true);
                if ($decodedData) {
                    $transactionUuid = $decodedData['transaction_uuid'] ?? $transactionUuid;
                    $totalAmount = $decodedData['total_amount'] ?? $totalAmount;

                    Log::info('eSewa decoded data', ['decoded' => $decodedData]);
                }
            }

            if (! $totalAmount) {
                return [
                    'success' => false,
                    'error' => 'Missing required verification parameter: total_amount',
                ];
            }

            // Make verification request to eSewa v2 API
            $response = Http::get($this->verifyUrl, [
                'product_code' => $this->merchantId,
                'total_amount' => $totalAmount,
                'transaction_uuid' => $transactionUuid,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('eSewa verification response', [
                    'response' => $responseData,
                    'transaction_uuid' => $transactionUuid,
                ]);

                // Check if payment status is COMPLETE
                if (isset($responseData['status']) && $responseData['status'] === 'COMPLETE') {
                    return [
                        'success' => true,
                        'status' => 'completed',
                        'transaction_id' => $responseData['ref_id'] ?? $transactionUuid,
                        'amount' => (float) $totalAmount,
                    ];
                }

                // Payment is pending or failed
                Log::warning('eSewa payment not completed', [
                    'transaction_uuid' => $transactionUuid,
                    'status' => $responseData['status'] ?? 'unknown',
                ]);

                return [
                    'success' => false,
                    'error' => 'Payment status: '.($responseData['status'] ?? 'unknown'),
                ];
            }

            Log::warning('eSewa payment verification failed', [
                'transaction_uuid' => $transactionUuid,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed',
            ];

        } catch (Exception $e) {
            Log::error('eSewa payment verification error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'trace' => $e->getTraceAsString(),
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
