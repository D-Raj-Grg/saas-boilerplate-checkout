<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('User isSuperAdmin method', function (): void {
    test('returns true for admin email', function (): void {
        $user = User::factory()->create(['email' => 'mahavirn@bsf.io']);

        expect($user->isSuperAdmin())->toBeTrue();
    });

    test('returns false for non-admin email', function (): void {
        $user = User::factory()->create(['email' => 'regular@example.com']);

        expect($user->isSuperAdmin())->toBeFalse();
    });

    test('supports multiple admin emails from config', function (): void {
        config(['app.superadmin_emails' => ['admin1@example.com', 'admin2@example.com']]);

        $admin1 = User::factory()->create(['email' => 'admin1@example.com']);
        $admin2 = User::factory()->create(['email' => 'admin2@example.com']);
        $regular = User::factory()->create(['email' => 'regular@example.com']);

        expect($admin1->isSuperAdmin())->toBeTrue();
        expect($admin2->isSuperAdmin())->toBeTrue();
        expect($regular->isSuperAdmin())->toBeFalse();
    });

    test('handles empty config gracefully', function (): void {
        config(['app.superadmin_emails' => []]);

        $user = User::factory()->create(['email' => 'mahavirn@bsf.io']);

        expect($user->isSuperAdmin())->toBeFalse();
    });
});
