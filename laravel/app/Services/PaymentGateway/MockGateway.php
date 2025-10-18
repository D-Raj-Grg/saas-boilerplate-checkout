<?php

namespace App\Services\PaymentGateway;

class MockGateway implements PaymentGatewayInterface
{
    /**
     * Initiate a mock payment and return a success URL
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, payment_url?: string, transaction_id?: string, error?: string}
     */
    public function initiatePayment(array $data): array
    {
        try {
            // Generate a mock transaction ID
            $transactionId = 'MOCK_' . uniqid() . '_' . time();
            
            // Redirect directly to frontend success page (the page will verify with backend)
            $frontendBase = rtrim(config('app.frontend_url'), '/');
            $paymentUrl = $frontendBase.'/payment/success?'.http_build_query([
                'payment_uuid' => $data['order_id'],
                'transaction_id' => $transactionId,
                'amount' => $data['amount'],
                'status' => 'success',
            ]);

            return [
                'success' => true,
                'payment_url' => $paymentUrl,
                'transaction_id' => $transactionId,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Mock payment initiation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify mock payment - always returns success for testing
     *
     * @return array{success: bool, status?: string, transaction_id?: string, amount?: float, error?: string}
     */
    public function verifyPayment(string $transactionId, array $additionalData = []): array
    {
        try {
            // Support multiple parameter formats for flexibility
            // 'amount' - mock gateway format
            // 'amt' - eSewa format
            $amount = $additionalData['amount']
                ?? $additionalData['amt']
                ?? 0;

            // For mock payments, we always return success
            return [
                'success' => true,
                'status' => 'completed',
                'transaction_id' => $transactionId,
                'amount' => (float) $amount,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Mock payment verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get mock payment status - always returns completed
     */
    public function getPaymentStatus(string $transactionId): string
    {
        return 'completed';
    }

    /**
     * Mock refund - always returns true for testing
     */
    public function refundPayment(string $transactionId, float $amount): bool
    {
        // Mock refund always succeeds
        return true;
    }
}
