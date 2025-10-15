<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Exception;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SureCartService
{
    /**
     * Create a customer in SureCart
     *
     * @return string|null Returns the customer ID on success, null on failure
     */
    public function createCustomer(User $user): ?string
    {
        $apiKey = config('services.surecart.api_key');

        if (empty($apiKey)) {
            Log::error('SureCart API key not configured');

            return null;
        }

        // Check if customer already exists for the current environment
        $existingCustomerId = $user->getSureCartCustomerId();
        if ($existingCustomerId) {
            return $existingCustomerId;
        }

        try {
            $liveMode = config('app.env') === 'production';

            $data = [
                'email' => strtolower($user->email),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'live_mode' => $liveMode,
            ];

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.surecart.com/v1/customers', $data);

            if (! $response->successful()) {
                Log::error('Failed to create SureCart customer', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            $responseData = $response->json();

            if (! isset($responseData['id']) || ! is_string($responseData['id'])) {
                Log::error('Invalid response from SureCart API', [
                    'user_id' => $user->id,
                    'response' => $responseData,
                ]);

                return null;
            }

            $customerId = $responseData['id'];

            // Store the customer ID in user metadata
            /** @var array<string, mixed> $metadata */
            $metadata = $user->metadata ?? [];

            /** @var array<string, string> $scCustomerIds */
            $scCustomerIds = isset($metadata['sc_customer_ids']) && is_array($metadata['sc_customer_ids'])
                ? $metadata['sc_customer_ids']
                : [];

            $environment = $liveMode ? 'live' : 'test';
            $scCustomerIds[$environment] = $customerId;

            $metadata['sc_customer_ids'] = $scCustomerIds;
            $user->update(['metadata' => $metadata]);

            return $customerId;

        } catch (Exception $e) {
            Log::error('Exception creating SureCart customer', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Get or create a SureCart customer for the user
     *
     * @return string|null Returns the customer ID
     */
    public function getOrCreateCustomer(User $user): ?string
    {
        $customerId = $user->getSureCartCustomerId();

        if ($customerId) {
            return $customerId;
        }

        return $this->createCustomer($user);
    }

    /**
     * Create magic login link and checkout URL
     *
     * @return array{login_id: string, checkout_url: string}|null
     */
    public function createLoginLinkAndCheckoutUrl(
        User $user,
        string $planSlug,
        string $source = 'pricing'
    ): ?array {
        $apiKey = config('services.surecart.api_key');

        if (empty($apiKey)) {
            Log::error('SureCart API key not configured');

            return null;
        }

        try {
            $liveMode = config('app.env') === 'production';

            // Get or create customer ID
            $customerId = $this->getOrCreateCustomer($user);
            if (! $customerId) {
                Log::error('Failed to get or create SureCart customer', ['user_id' => $user->id]);

                return null;
            }

            // Get user's current organization
            $organization = $user->currentOrganization;

            // Get the price ID for the plan
            $plan = Plan::where('slug', $planSlug)->first();
            if (! $plan) {
                Log::error('Plan not found', ['plan_slug' => $planSlug]);

                return null;
            }

            // Try to get from config price_mapping
            $priceMapping = config('services.surecart.price_mapping', []);
            $priceId = $priceMapping[$planSlug] ?? null;

            if (! $priceId) {
                Log::error('Price ID not found for plan', ['plan_slug' => $planSlug]);

                return null;
            }

            // Prepare headers for both requests
            $headers = [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ];

            // Prepare customer link data
            $customerLinkData = [
                'customer' => $customerId,
                'email' => strtolower($user->email),
                'live_mode' => $liveMode,
            ];

            // Prepare checkout data
            $checkoutMetadata = [
                'source' => $source,
                'organization_id' => $organization ? $organization->uuid : null,
            ];

            $checkoutData = [
                'metadata' => $checkoutMetadata,
                'email' => strtolower($user->email),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'customer' => $customerId,
                'live_mode' => $liveMode,
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price' => $priceId,
                    ],
                ],
            ];

            // Make both requests in parallel using HTTP pool
            $responses = Http::pool(fn (Pool $pool) => [
                $pool->withHeaders($headers)->post('https://api.surecart.com/v1/customer_links', [
                    'customer_link' => $customerLinkData,
                ]),
                $pool->withHeaders($headers)->post('https://api.surecart.com/v1/checkouts', [
                    'checkout' => $checkoutData,
                ]),
            ]);

            // Check if both requests were successful
            if (! $responses[0]->successful() || ! $responses[1]->successful()) {
                Log::error('Failed to create customer link or checkout', [
                    'customer_link_status' => $responses[0]->status(),
                    'checkout_status' => $responses[1]->status(),
                    'customer_link_response' => $responses[0]->body(),
                    'checkout_response' => $responses[1]->body(),
                ]);

                return null;
            }

            $customerLinkResponse = $responses[0]->json();
            $checkoutResponse = $responses[1]->json();

            if (! isset($customerLinkResponse['id']) || ! isset($checkoutResponse['id'])) {
                Log::error('Invalid response from SureCart API', [
                    'customer_link_response' => $customerLinkResponse,
                    'checkout_response' => $checkoutResponse,
                ]);

                return null;
            }

            // Build the checkout URL
            $checkoutPath = $this->getCheckoutPath($planSlug);
            $checkoutUrl = $checkoutPath.'?checkout_id='.$checkoutResponse['id'].'&no_cart=true';

            return [
                'login_id' => $customerLinkResponse['id'],
                'checkout_url' => $checkoutUrl,
            ];

        } catch (Exception $e) {
            Log::error('Exception creating login link and checkout URL', [
                'user_id' => $user->id,
                'plan_slug' => $planSlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Get login link for a user
     *
     * @return string|null Returns the login ID on success, null on failure
     */
    public function getLoginLink(User $user): ?string
    {
        $apiKey = config('services.surecart.api_key');

        if (empty($apiKey)) {
            Log::error('SureCart API key not configured');

            return null;
        }

        try {
            $liveMode = config('app.env') === 'production';

            // Get or create customer ID
            $customerId = $this->getOrCreateCustomer($user);
            if (! $customerId) {
                Log::error('Failed to get or create SureCart customer', ['user_id' => $user->id]);

                return null;
            }

            // Prepare customer link data
            $customerLinkData = [
                'customer' => $customerId,
                'email' => strtolower($user->email),
                'live_mode' => $liveMode,
            ];

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.surecart.com/v1/customer_links', [
                'customer_link' => $customerLinkData,
            ]);

            if (! $response->successful()) {
                Log::error('Failed to create customer link', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            $customerLinkResponse = $response->json();

            if (! isset($customerLinkResponse['id'])) {
                Log::error('Invalid response from SureCart API', [
                    'customer_link_response' => $customerLinkResponse,
                ]);

                return null;
            }

            return $customerLinkResponse['id'];

        } catch (Exception $e) {
            Log::error('Exception creating login link', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Get the checkout path based on the plan slug
     */
    protected function getCheckoutPath(string $planSlug): string
    {
        // You can customize this based on your billing store structure
        // For now, using a generic checkout path
        return '/checkout/';
    }
}
