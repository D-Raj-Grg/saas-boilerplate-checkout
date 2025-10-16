<?php

namespace App\Services\PaymentGateway;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonepayGateway implements PaymentGatewayInterface
{
    protected string $merchantCode;

    protected string $secret;

    protected string $apiUrl;

    public function __construct()
    {
        $this->merchantCode = config('payment-gateways.fonepay.merchant_code');
        $this->secret = config('payment-gateways.fonepay.secret');
        $this->apiUrl = config('payment-gateways.fonepay.api_url');
    }

    /**
     * @param  array{amount: float, order_id: string, product_name: string}  $data
     */
    public function initiatePayment(array $data): array
    {
        try {
            // Fonepay implementation (simplified - adjust based on actual API)
            $amount = $data['amount'];
            $orderId = $data['order_id'];

            // Generate signature for Fonepay
            $signature = $this->generateSignature($orderId, $amount);

            // Build Fonepay payment URL
            $paymentUrl = $this->apiUrl.'?'.http_build_query([
                'merchant' => $this->merchantCode,
                'order_id' => $orderId,
                'amount' => $amount,
                'signature' => $signature,
                'return_url' => config('frontend.url').'/payment/success',
            ]);

            return [
                'success' => true,
                'payment_url' => $paymentUrl,
                'transaction_id' => $orderId,
            ];

        } catch (Exception $e) {
            Log::error('Fonepay payment initiation error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to initiate Fonepay payment',
            ];
        }
    }

    public function verifyPayment(string $transactionId, array $additionalData = []): array
    {
        try {
            // Fonepay verification (implement based on their API)
            Log::info('Fonepay payment verification', ['transaction_id' => $transactionId]);

            // For now, return pending status
            // Implement actual verification based on Fonepay documentation
            return [
                'success' => false,
                'error' => 'Fonepay verification not yet implemented',
            ];

        } catch (Exception $e) {
            Log::error('Fonepay payment verification error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed',
            ];
        }
    }

    public function getPaymentStatus(string $transactionId): string
    {
        // Implement based on Fonepay API
        return 'pending';
    }

    public function refundPayment(string $transactionId, float $amount): bool
    {
        Log::info('Fonepay refund requested', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        return false;
    }

    protected function generateSignature(string $orderId, float $amount): string
    {
        // Generate signature based on Fonepay requirements
        // This is a simplified version - adjust based on actual documentation
        return hash_hmac('sha256', $orderId.$amount, $this->secret);
    }
}
