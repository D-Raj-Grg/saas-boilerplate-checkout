<?php

namespace App\Services\PaymentGateway;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KhaltiGateway implements PaymentGatewayInterface
{
    protected string $publicKey;

    protected string $secretKey;

    protected string $apiUrl;

    protected string $returnUrl;

    protected string $websiteUrl;

    public function __construct()
    {
        $this->publicKey = config('payment-gateways.khalti.public_key');
        $this->secretKey = config('payment-gateways.khalti.secret_key');
        $this->apiUrl = config('payment-gateways.khalti.api_url');
        $this->returnUrl = config('payment-gateways.khalti.return_url');
        $this->websiteUrl = config('payment-gateways.khalti.website_url');
    }

    /**
     * @param  array{amount: float, order_id: string, product_name: string, customer_email?: string, customer_name?: string}  $data
     */
    public function initiatePayment(array $data): array
    {
        try {
            // Khalti expects amount in paisa (1 rupee = 100 paisa)
            $amountInPaisa = (int) ($data['amount'] * 100);
            $orderId = $data['order_id'];
            $productName = $data['product_name'] ?? 'SaaS Subscription';

            // Create payment initiation request
            $response = Http::withHeaders([
                'Authorization' => 'Key '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'epayment/initiate/', [
                'return_url' => $this->returnUrl.'?payment_uuid='.$orderId,
                'website_url' => $this->websiteUrl,
                'amount' => $amountInPaisa,
                'purchase_order_id' => $orderId,
                'purchase_order_name' => $productName,
                'customer_info' => [
                    'name' => $data['customer_name'] ?? 'Customer',
                    'email' => $data['customer_email'] ?? '',
                ],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['payment_url'])) {
                    return [
                        'success' => true,
                        'payment_url' => $responseData['payment_url'],
                        'transaction_id' => $responseData['pidx'] ?? $orderId,
                    ];
                }
            }

            Log::error('Khalti payment initiation failed', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to initiate Khalti payment',
            ];

        } catch (Exception $e) {
            Log::error('Khalti payment initiation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to initiate Khalti payment',
            ];
        }
    }

    /**
     * Verify payment with Khalti
     *
     * @param  array{pidx?: string}  $additionalData
     */
    public function verifyPayment(string $transactionId, array $additionalData = []): array
    {
        try {
            // Khalti uses 'pidx' (payment index) for verification
            $pidx = $additionalData['pidx'] ?? $transactionId;

            // Lookup payment details
            $response = Http::withHeaders([
                'Authorization' => 'Key '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'epayment/lookup/', [
                'pidx' => $pidx,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['status']) && $responseData['status'] === 'Completed') {
                    $amountInRupees = ($responseData['total_amount'] ?? 0) / 100;

                    return [
                        'success' => true,
                        'status' => 'completed',
                        'transaction_id' => $responseData['transaction_id'] ?? $pidx,
                        'amount' => $amountInRupees,
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Payment not completed. Status: '.($responseData['status'] ?? 'unknown'),
                ];
            }

            Log::warning('Khalti payment verification failed', [
                'transaction_id' => $transactionId,
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed',
            ];

        } catch (Exception $e) {
            Log::error('Khalti payment verification error', [
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
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Key '.$this->secretKey,
            ])->post($this->apiUrl.'epayment/lookup/', [
                'pidx' => $transactionId,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return strtolower($data['status'] ?? 'pending');
            }

            return 'pending';
        } catch (Exception $e) {
            Log::error('Khalti status check failed', ['error' => $e->getMessage()]);

            return 'pending';
        }
    }

    public function refundPayment(string $transactionId, float $amount): bool
    {
        // Khalti refund API (if available)
        Log::info('Khalti refund requested', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        // Implement refund API when available
        return false;
    }
}
