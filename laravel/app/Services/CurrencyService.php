<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Format a price for display with currency symbol
     */
    public function format(float $amount, ?string $currency = null, bool $includeSymbol = true): string
    {
        $currency = $currency ?? config('currency.default');
        $config = $this->getCurrencyConfig($currency);

        if (! $config) {
            return number_format($amount, 2);
        }

        $formatted = number_format(
            $amount,
            $config['decimal_places'],
            $config['decimal_separator'],
            $config['thousands_separator']
        );

        if (! $includeSymbol) {
            return $formatted;
        }

        return $config['symbol_position'] === 'before'
            ? $config['symbol'].' '.$formatted
            : $formatted.' '.$config['symbol'];
    }

    /**
     * Get currency configuration
     */
    public function getCurrencyConfig(string $currency): ?array
    {
        return config("currency.supported.{$currency}");
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rates = $this->getExchangeRates();

        // Convert to base currency (NPR) first
        $baseAmount = $amount / ($rates[$from] ?? 1);

        // Then convert to target currency
        return $baseAmount * ($rates[$to] ?? 1);
    }

    /**
     * Get exchange rates (cached)
     */
    public function getExchangeRates(): array
    {
        return Cache::remember('currency_exchange_rates', 3600, function () {
            // In production, fetch from external API
            // For now, use config values
            return config('currency.exchange_rates');
        });
    }

    /**
     * Get currency for a specific market
     */
    public function getCurrencyForMarket(string $market): string
    {
        return config("currency.markets.{$market}", config('currency.default'));
    }

    /**
     * Get user's preferred currency
     */
    public function getUserCurrency(?User $user = null): string
    {
        if ($user && $user->currency_preference) {
            return $user->currency_preference;
        }

        // TODO: Auto-detect from IP/location if enabled
        if (config('currency.auto_detect')) {
            return $this->detectCurrencyFromRequest();
        }

        return config('currency.default');
    }

    /**
     * Detect currency from request (IP-based geolocation)
     */
    protected function detectCurrencyFromRequest(): string
    {
        // TODO: Implement IP-based currency detection
        // You can use services like ipapi.co, geoip, etc.
        return config('currency.default');
    }

    /**
     * Get available payment gateways for a currency
     *
     * @return array<string>
     */
    public function getAvailableGatewaysForCurrency(string $currency): array
    {
        $gateways = [];
        $gatewaySupport = config('currency.gateway_support', []);

        foreach ($gatewaySupport as $gateway => $supportedCurrencies) {
            if (in_array($currency, $supportedCurrencies)) {
                $gateways[] = $gateway;
            }
        }

        return $gateways;
    }

    /**
     * Check if a gateway supports a currency
     */
    public function gatewaySupportsCurrency(string $gateway, string $currency): bool
    {
        $supportedCurrencies = config("currency.gateway_support.{$gateway}", []);

        return in_array($currency, $supportedCurrencies);
    }

    /**
     * Get all supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return config('currency.supported', []);
    }

    /**
     * Get currency symbol
     */
    public function getSymbol(string $currency): string
    {
        return config("currency.supported.{$currency}.symbol", $currency);
    }

    /**
     * Fetch latest exchange rates from external API
     * (For production use)
     */
    public function updateExchangeRates(): void
    {
        try {
            // Example: Use exchangerate-api.com or similar service
            // $response = Http::get('https://api.exchangerate-api.com/v4/latest/NPR');
            //
            // if ($response->successful()) {
            //     $rates = $response->json('rates');
            //     Cache::put('currency_exchange_rates', $rates, 86400); // Cache for 24 hours
            // }

            Log::info('Exchange rates updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update exchange rates', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
