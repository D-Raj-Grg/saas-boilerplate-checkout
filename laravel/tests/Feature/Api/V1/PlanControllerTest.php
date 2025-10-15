<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->apiPrefix = '/api/v1';

    // Set up config
    config(['services.surecart.api_key' => 'test_api_key']);
    config(['billing-store.url' => 'https://billing.example.com']);
});

describe('Get Checkout URL', function (): void {
    test('authenticated user can get checkout url', function (): void {
        // Create user with organization
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'metadata' => [
                'sc_customer_ids' => [
                    'test' => 'cus_test123',
                ],
            ],
        ]);

        $organization = Organization::factory()->ownedBy($user)->create([
            'name' => 'Test Organization',
        ]);

        $user->update(['current_organization_id' => $organization->id]);

        // Create plan
        $plan = Plan::factory()->create([
            'name' => 'Starter Plan',
            'slug' => 'starter',
        ]);

        // Add price mapping to config
        config(['services.surecart.price_mapping' => [
            'starter' => 'price_test123',
        ]]);

        // Mock HTTP requests
        Http::fake([
            'https://api.surecart.com/v1/customer_links' => Http::response([
                'id' => 'link_test123',
            ], 200),
            'https://api.surecart.com/v1/checkouts' => Http::response([
                'id' => 'checkout_test123',
            ], 200),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/checkout-url", [
            'plan_slug' => 'starter',
            'source' => 'pricing',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'checkout_url',
                ],
            ]);

        expect($response->json('data.checkout_url'))->toContain('customer_link_id=link_test123');
    });

    test('checkout url includes coupon when provided', function (): void {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'metadata' => [
                'sc_customer_ids' => [
                    'test' => 'cus_test123',
                ],
            ],
        ]);

        $organization = Organization::factory()->ownedBy($user)->create();
        $user->update(['current_organization_id' => $organization->id]);

        Plan::factory()->create(['slug' => 'starter']);

        config(['services.surecart.price_mapping' => ['starter' => 'price_test123']]);

        Http::fake([
            'https://api.surecart.com/v1/customer_links' => Http::response(['id' => 'link_test123'], 200),
            'https://api.surecart.com/v1/checkouts' => Http::response(['id' => 'checkout_test123'], 200),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/checkout-url", [
            'plan_slug' => 'starter',
            'coupon' => 'SAVE20',
        ]);

        $response->assertStatus(200);
        $checkoutUrl = $response->json('data.checkout_url');
        expect($checkoutUrl)
            ->toContain('customer_link_id=link_test123');

        // Decode the path parameter to check for coupon
        $decodedUrl = urldecode($checkoutUrl);
        expect($decodedUrl)->toContain('coupon=SAVE20');
    });

    test('checkout url includes affiliate code when provided', function (): void {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'metadata' => [
                'sc_customer_ids' => [
                    'test' => 'cus_test123',
                ],
            ],
        ]);

        $organization = Organization::factory()->ownedBy($user)->create();
        $user->update(['current_organization_id' => $organization->id]);

        Plan::factory()->create(['slug' => 'starter']);

        config(['services.surecart.price_mapping' => ['starter' => 'price_test123']]);

        Http::fake([
            'https://api.surecart.com/v1/customer_links' => Http::response(['id' => 'link_test123'], 200),
            'https://api.surecart.com/v1/checkouts' => Http::response(['id' => 'checkout_test123'], 200),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/checkout-url", [
            'plan_slug' => 'starter',
            'aff' => 'affiliate123',
        ]);

        $response->assertStatus(200);
        $checkoutUrl = $response->json('data.checkout_url');
        expect($checkoutUrl)
            ->toContain('customer_link_id=link_test123');

        // Decode the path parameter to check for affiliate
        $decodedUrl = urldecode($checkoutUrl);
        expect($decodedUrl)->toContain('aff=affiliate123');
    });

    test('creates customer if not exists', function (): void {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'metadata' => null, // No customer ID yet
        ]);

        $organization = Organization::factory()->ownedBy($user)->create();
        $user->update(['current_organization_id' => $organization->id]);

        Plan::factory()->create(['slug' => 'starter']);

        config(['services.surecart.price_mapping' => ['starter' => 'price_test123']]);

        // Mock customer creation and checkout
        Http::fake([
            'https://api.surecart.com/v1/customers' => Http::response([
                'id' => 'cus_new123',
            ], 200),
            'https://api.surecart.com/v1/customer_links' => Http::response(['id' => 'link_test123'], 200),
            'https://api.surecart.com/v1/checkouts' => Http::response(['id' => 'checkout_test123'], 200),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/checkout-url", [
            'plan_slug' => 'starter',
        ]);

        $response->assertStatus(200);

        // Verify customer was created
        $user->refresh();
        expect($user->getSureCartCustomerId())->toBe('cus_new123');
    });

    test('unauthenticated user can get checkout url', function (): void {
        Plan::factory()->create(['slug' => 'starter']);

        $response = $this->postJson("{$this->apiPrefix}/plans/checkout-url", [
            'plan_slug' => 'starter',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'checkout_url',
                ],
            ]);
    });

    test('fails with missing plan_slug', function (): void {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/checkout-url", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_slug']);
    });

    test('fails with invalid plan_slug', function (): void {
        $user = User::factory()->create([
            'metadata' => [
                'sc_customer_ids' => [
                    'test' => 'cus_test123',
                ],
            ],
        ]);

        $organization = Organization::factory()->ownedBy($user)->create();
        $user->update(['current_organization_id' => $organization->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/checkout-url", [
            'plan_slug' => 'non_existent_plan',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Unable to create checkout link. Please try again.',
            ]);
    });

    test('handles surecart api failures gracefully', function (): void {
        $user = User::factory()->create([
            'metadata' => [
                'sc_customer_ids' => [
                    'test' => 'cus_test123',
                ],
            ],
        ]);

        $organization = Organization::factory()->ownedBy($user)->create();
        $user->update(['current_organization_id' => $organization->id]);

        Plan::factory()->create(['slug' => 'starter']);

        config(['services.surecart.price_mapping' => ['starter' => 'price_test123']]);

        // Mock API failure
        Http::fake([
            'https://api.surecart.com/v1/*' => Http::response([], 500),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/checkout-url", [
            'plan_slug' => 'starter',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Unable to create checkout link. Please try again.',
            ]);
    });

    test('works without current organization', function (): void {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'metadata' => [
                'sc_customer_ids' => [
                    'test' => 'cus_test123',
                ],
            ],
            'current_organization_id' => null, // No current organization
        ]);

        Plan::factory()->create(['slug' => 'starter']);

        config(['services.surecart.price_mapping' => ['starter' => 'price_test123']]);

        Http::fake([
            'https://api.surecart.com/v1/customer_links' => Http::response(['id' => 'link_test123'], 200),
            'https://api.surecart.com/v1/checkouts' => Http::response(['id' => 'checkout_test123'], 200),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/checkout-url", [
            'plan_slug' => 'starter',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify checkout was created without organization_id in metadata
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return $request->url() === 'https://api.surecart.com/v1/checkouts' &&
                   ($body['checkout']['metadata']['organization_id'] ?? null) === null;
        });
    });
});

describe('Get Customer Dashboard URL', function (): void {
    test('authenticated user can get customer dashboard url', function (): void {
        // Create user with existing customer ID
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'metadata' => [
                'sc_customer_ids' => [
                    'test' => 'cus_test123',
                ],
            ],
        ]);

        // Mock HTTP request for customer link
        Http::fake([
            'https://api.surecart.com/v1/customer_links' => Http::response([
                'id' => 'link_test123',
            ], 200),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/customer-dashboard-url");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'customer_dashboard_url',
                ],
            ]);

        $dashboardUrl = $response->json('data.customer_dashboard_url');
        expect($dashboardUrl)
            ->toContain('customer_link_id=link_test123')
            ->toContain('customer-dashboard');
    });

    test('creates customer if not exists when getting dashboard url', function (): void {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'metadata' => null, // No customer ID yet
        ]);

        // Mock customer creation and customer link
        Http::fake([
            'https://api.surecart.com/v1/customers' => Http::response([
                'id' => 'cus_new123',
            ], 200),
            'https://api.surecart.com/v1/customer_links' => Http::response([
                'id' => 'link_test123',
            ], 200),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/customer-dashboard-url");

        $response->assertStatus(200);

        // Verify customer was created
        $user->refresh();
        expect($user->getSureCartCustomerId())->toBe('cus_new123');

        // Verify dashboard URL is returned
        $dashboardUrl = $response->json('data.customer_dashboard_url');
        expect($dashboardUrl)->toContain('customer_link_id=link_test123');
    });

    test('unauthenticated user cannot get customer dashboard url', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/plans/customer-dashboard-url");

        $response->assertStatus(401);
    });

    test('handles surecart api failures gracefully for dashboard url', function (): void {
        $user = User::factory()->create([
            'metadata' => [
                'sc_customer_ids' => [
                    'test' => 'cus_test123',
                ],
            ],
        ]);

        // Mock API failure
        Http::fake([
            'https://api.surecart.com/v1/customer_links' => Http::response([], 500),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/customer-dashboard-url");

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Unable to create customer dashboard link. Please try again.',
            ]);
    });

    test('dashboard url uses correct billing store url', function (): void {
        $user = User::factory()->create([
            'metadata' => [
                'sc_customer_ids' => [
                    'test' => 'cus_test123',
                ],
            ],
        ]);

        Http::fake([
            'https://api.surecart.com/v1/customer_links' => Http::response([
                'id' => 'link_test456',
            ], 200),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/customer-dashboard-url");

        $response->assertStatus(200);

        $dashboardUrl = $response->json('data.customer_dashboard_url');

        // Should contain the billing store URL from config
        expect($dashboardUrl)->toStartWith('https://billing.example.com/surecart/redirect/');
    });

    test('dashboard url path is properly encoded', function (): void {
        $user = User::factory()->create([
            'metadata' => [
                'sc_customer_ids' => [
                    'test' => 'cus_test123',
                ],
            ],
        ]);

        Http::fake([
            'https://api.surecart.com/v1/customer_links' => Http::response([
                'id' => 'link_test789',
            ], 200),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/plans/customer-dashboard-url");

        $response->assertStatus(200);

        $dashboardUrl = $response->json('data.customer_dashboard_url');

        // The path parameter should be URL encoded
        expect($dashboardUrl)->toContain('path=');

        // Extract and decode the path parameter
        parse_str(parse_url($dashboardUrl, PHP_URL_QUERY), $queryParams);
        expect($queryParams['path'])->toBe('/customer-dashboard');
    });
});
