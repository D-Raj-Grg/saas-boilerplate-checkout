<?php

use App\Models\Organization;
use App\Models\User;
use App\Models\Waitlist;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->apiPrefix = '/api/v1';
});

describe('Waitlist Join Endpoint', function (): void {
    test('can join waitlist with valid data', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/waitlist/join", [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'metadata' => ['source' => 'landing_page'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'uuid',
                    'email',
                    'full_name',
                    'status',
                    'joined_at',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('waitlists', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'pending',
        ]);
    });

    test('can join waitlist with only email', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/waitlist/join", [
            'email' => 'jane@example.com',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('waitlists', [
            'email' => 'jane@example.com',
            'first_name' => null,
            'last_name' => null,
            'status' => 'pending',
        ]);
    });

    test('updates existing entry when email already exists', function (): void {
        Waitlist::create([
            'email' => 'existing@example.com',
            'first_name' => 'Old',
            'metadata' => ['old' => 'data'],
        ]);

        $response = $this->postJson("{$this->apiPrefix}/waitlist/join", [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => 'existing@example.com',
            'metadata' => ['new' => 'data'],
        ]);

        $response->assertStatus(201);

        $waitList = Waitlist::where('email', 'existing@example.com')->first();
        expect($waitList->first_name)->toBe('New');
        expect($waitList->last_name)->toBe('Name');
        expect($waitList->metadata)->toEqual(['old' => 'data', 'new' => 'data']);
    });

    test('validates required email field', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/waitlist/join", [
            'first_name' => 'John',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    });

    test('validates email format', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/waitlist/join", [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    });

    test('validates field lengths', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/waitlist/join", [
            'first_name' => str_repeat('a', 101),
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('first_name');
    });

    test('respects rate limiting', function (): void {
        // Make multiple requests to hit rate limit
        for ($i = 0; $i < 61; $i++) {
            $this->postJson("{$this->apiPrefix}/waitlist/join", [
                'email' => "test{$i}@example.com",
            ]);
        }

        $response = $this->postJson("{$this->apiPrefix}/waitlist/join", [
            'email' => 'final@example.com',
        ]);

        $response->assertStatus(429);
    });
});

describe('Waitlist Admin Endpoints', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);
        $this->user->update(['current_organization_id' => $this->organization->id]);

        $this->actingAs($this->user, 'sanctum');
    });

    test('can list waitlist entries', function (): void {
        Waitlist::factory()->count(3)->create();

        $response = $this->getJson("{$this->apiPrefix}/waitlist");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'entries' => [
                        '*' => [
                            'uuid',
                            'first_name',
                            'last_name',
                            'full_name',
                            'email',
                            'status',
                            'metadata',
                            'joined_at',
                            'contacted_at',
                            'converted_at',
                        ],
                    ],
                    'stats',
                ],
                'message',
            ]);
    });

    test('can get waitlist statistics', function (): void {
        Waitlist::factory()->pending()->count(2)->create();
        Waitlist::factory()->contacted()->create();
        Waitlist::factory()->converted()->create();

        $response = $this->getJson("{$this->apiPrefix}/waitlist/stats");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 4,
                    'pending' => 2,
                    'contacted' => 1,
                    'converted' => 1,
                ],
            ]);
    });

    test('can export waitlist to CSV', function (): void {
        Waitlist::factory()->count(2)->create();

        $response = $this->getJson("{$this->apiPrefix}/waitlist/export");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'csv',
                    'filename',
                ],
                'message',
            ]);

        expect($response->json('data.csv'))->toContain('Email,First Name,Last Name,Status,Joined Date');
    });

    test('can update entry status to contacted', function (): void {
        $waitList1 = Waitlist::factory()->pending()->create();
        $waitList2 = Waitlist::factory()->pending()->create();

        $response = $this->patchJson("{$this->apiPrefix}/waitlist/status", [
            'uuids' => [$waitList1->uuid, $waitList2->uuid],
            'action' => 'contact',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['updated_count' => 2],
            ]);

        $this->assertDatabaseHas('waitlists', [
            'uuid' => $waitList1->uuid,
            'status' => 'contacted',
        ]);
    });

    test('can update entry status to converted', function (): void {
        $waitList = Waitlist::factory()->contacted()->create();

        $response = $this->patchJson("{$this->apiPrefix}/waitlist/status", [
            'uuids' => [$waitList->uuid],
            'action' => 'convert',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('waitlists', [
            'uuid' => $waitList->uuid,
            'status' => 'converted',
        ]);
    });

    test('can delete waitlist entries', function (): void {
        $waitList1 = Waitlist::factory()->create();
        $waitList2 = Waitlist::factory()->create();

        $response = $this->deleteJson("{$this->apiPrefix}/waitlist", [
            'uuids' => [$waitList1->uuid, $waitList2->uuid],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['deleted_count' => 2],
            ]);

        $this->assertDatabaseMissing('waitlists', ['uuid' => $waitList1->uuid]);
        $this->assertDatabaseMissing('waitlists', ['uuid' => $waitList2->uuid]);
    });

    test('admin endpoints work with proper authorization', function (): void {
        // Test with authorized admin user
        $adminUser = User::factory()->create(['email' => 'mahavirn@bsf.io']);
        $this->actingAs($adminUser, 'sanctum');

        $response = $this->getJson("{$this->apiPrefix}/waitlist");
        $response->assertStatus(200);

        $response = $this->getJson("{$this->apiPrefix}/waitlist/stats");
        $response->assertStatus(200);

        $response = $this->getJson("{$this->apiPrefix}/waitlist/export");
        $response->assertStatus(200);

        // Test with non-admin user in non-testing environment
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $this->actingAs($otherUser, 'sanctum');

        // Mock non-testing environment temporarily
        app()->instance('env', 'local');

        $response = $this->getJson("{$this->apiPrefix}/waitlist");
        $response->assertStatus(403);

        // Restore testing environment
        app()->instance('env', 'testing');
    });

    test('validates update status request', function (): void {
        $response = $this->patchJson("{$this->apiPrefix}/waitlist/status", [
            'uuids' => [],
            'action' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['uuids', 'action']);
    });

    test('validates delete request', function (): void {
        $response = $this->deleteJson("{$this->apiPrefix}/waitlist", [
            'uuids' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('uuids');
    });

    test('can filter waitlist entries by status', function (): void {
        Waitlist::factory()->pending()->create();
        Waitlist::factory()->contacted()->create();

        $response = $this->getJson("{$this->apiPrefix}/waitlist?status=pending");

        $response->assertStatus(200);
        expect(count($response->json('data.entries')))->toBe(1);
        expect($response->json('data.entries.0.status'))->toBe('pending');
    });

    test('can search waitlist entries', function (): void {
        Waitlist::factory()->create([
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);
        Waitlist::factory()->create([
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
        ]);

        $response = $this->getJson("{$this->apiPrefix}/waitlist?search=john");

        $response->assertStatus(200);
        expect(count($response->json('data.entries')))->toBe(1);
        expect($response->json('data.entries.0.first_name'))->toBe('John');
    });
});
