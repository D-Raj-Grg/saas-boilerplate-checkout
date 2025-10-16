<?php

namespace App\Services\PaymentGateway;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment and return payment URL
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, payment_url?: string, transaction_id?: string, error?: string}
     */
    public function initiatePayment(array $data): array;

    /**
     * Verify payment with gateway
     *
     * @return array{success: bool, status?: string, transaction_id?: string, amount?: float, error?: string}
     */
    public function verifyPayment(string $transactionId, array $additionalData = []): array;

    /**
     * Get payment status from gateway
     */
    public function getPaymentStatus(string $transactionId): string;

    /**
     * Refund a payment (optional, not all gateways support this)
     */
    public function refundPayment(string $transactionId, float $amount): bool;
}
