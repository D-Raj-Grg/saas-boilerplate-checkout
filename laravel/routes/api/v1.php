<?php

use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\UserSessionController;
use App\Http\Controllers\Api\V1\WaitlistController;
use App\Http\Controllers\Api\V1\WebSocketController;
use App\Http\Controllers\Api\V1\WorkspaceController;
use App\Http\Controllers\Api\V1\WorkspaceSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes with Comprehensive Rate Limiting
|--------------------------------------------------------------------------
*/

// Apply rate limit headers middleware to all API routes
Route::middleware(['App\Http\Middleware\RateLimitHeaders'])->group(function (): void {

    // Public routes with rate limiting
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->name('login');

        // Google OAuth routes
        Route::get('/google/redirect', [GoogleAuthController::class, 'redirect']);
        Route::get('/google/callback', [GoogleAuthController::class, 'callback']);
        Route::post('/google/exchange', [GoogleAuthController::class, 'exchange']);
    });

    // Password reset routes with stricter rate limiting
    Route::middleware('throttle:password-reset')->group(function (): void {
        Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword']);
        Route::post('/password/verify-token', [PasswordResetController::class, 'verifyToken']);
        Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
    });

    // Email verification routes
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('/email/verify', [EmailVerificationController::class, 'verify']);
    });

    // Wait list routes (public)
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('/waitlist/join', [WaitlistController::class, 'join']);
    });

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Public invitation endpoints (no auth required)
    Route::middleware(['uuid.validate:token', 'throttle:invitations'])->group(function (): void {
        Route::get('/invitations/{token}/preview', [InvitationController::class, 'preview']);
        Route::get('/invitations/{token}/check-status', [InvitationController::class, 'checkStatus']);
    });

    // Public plans endpoint
    Route::middleware('throttle:api')->group(function (): void {
        Route::get('/plans', [PlanController::class, 'index']);
    });

    // Public payment endpoints (support guest checkout)
    Route::middleware('throttle:api')->group(function (): void {
        Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
        Route::get('/payments/{payment:uuid}/verify', [PaymentController::class, 'verify']);
    });

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function (): void {

        // Protected payment routes
        Route::middleware('throttle:api')->group(function (): void {
            Route::get('/payments/{payment:uuid}/status', [PaymentController::class, 'status']);
            Route::get('/payments/history', [PaymentController::class, 'history']);
            Route::post('/payments/{payment:uuid}/refund', [PaymentController::class, 'refund']); // Admin only
        });

        // General API rate limit for basic operations
        Route::middleware('throttle:api')->group(function (): void {
            // Authentication
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);
            Route::put('/user', [AuthController::class, 'update']);
            Route::get('/me', [AuthController::class, 'me']);

            // Email verification
            Route::post('/email/resend', [EmailVerificationController::class, 'resend']);
            Route::get('/email/verification-status', [EmailVerificationController::class, 'status']);

            // Dashboard
            Route::get('/dashboard', [DashboardController::class, 'index']);

            // User session management
            Route::middleware('uuid.validate:organization')->post('/user/current-organization/{organization:uuid}', [UserSessionController::class, 'setCurrentOrganization']);
            Route::middleware('uuid.validate:workspace')->post('/user/current-workspace/{workspace:uuid}', [UserSessionController::class, 'setCurrentWorkspace']);
            Route::post('/user/clear-session', [UserSessionController::class, 'clearSession']);

            // WebSocket listener management (authenticated)
            Route::post('/websocket/connect', [WebSocketController::class, 'connect']);
            Route::post('/websocket/disconnect', [WebSocketController::class, 'disconnect']);
        });

        // Organizations
        Route::middleware(['throttle:api'])->group(function (): void {
            Route::get('/organizations', [OrganizationController::class, 'index']);  // List all accessible organizations
            Route::post('/organization', [OrganizationController::class, 'store']); // Create new organization
        });

        // Current Organization operations (context-based)
        Route::middleware(['context:organization', 'throttle:api'])->group(function (): void {
            Route::get('/organization', [OrganizationController::class, 'show']);
            Route::put('/organization', [OrganizationController::class, 'update']);
            Route::delete('/organization', [OrganizationController::class, 'destroy']);
            Route::post('/organization/transfer-ownership', [OrganizationController::class, 'transferOwnership']);
            Route::get('/organization/stats', [OrganizationController::class, 'stats']);

            // Organization members
            Route::get('/organization/members', [OrganizationController::class, 'members']);
            Route::delete('/organization/members/{user:uuid}', [OrganizationController::class, 'removeMember']);
            Route::patch('/organization/members/{user:uuid}/role', [OrganizationController::class, 'changeMemberRole']);

            // Workspaces within current organization
            Route::get('/organization/workspaces', [WorkspaceController::class, 'index']); // List workspaces in current org
            Route::post('/organization/workspace', [WorkspaceController::class, 'store']); // Create workspace in current org

            // Direct workspace operations by UUID
            Route::put('/workspaces/{workspace:uuid}', [WorkspaceController::class, 'updateByUuid']);

            // Organization invitations (using current organization context)
            Route::middleware('throttle:invitations')->group(function (): void {
                Route::get('/invitations', [InvitationController::class, 'index']);
                Route::post('/invitations', [InvitationController::class, 'store']);
            });
        });

        // Current Workspace operations (context-based)
        Route::middleware(['context:workspace', 'throttle:api'])->group(function (): void {
            Route::get('/workspace', [WorkspaceController::class, 'show']);
            Route::put('/workspace', [WorkspaceController::class, 'update']);
            Route::delete('/workspace', [WorkspaceController::class, 'destroy']);
            Route::post('/workspace/duplicate', [WorkspaceController::class, 'duplicate']);
            Route::post('/workspace/transfer-ownership', [WorkspaceController::class, 'transferOwnership']);

            // Workspace members
            Route::get('/workspace/members', [WorkspaceController::class, 'members']);
            Route::post('/workspace/members', [WorkspaceController::class, 'addMember']);
            Route::delete('/workspace/members/{user}', [WorkspaceController::class, 'removeMember']);
            Route::patch('/workspace/members/{user}/role', [WorkspaceController::class, 'changeRole']);
        });

        // Workspace Settings
        Route::middleware('throttle:workspace-settings')->group(function (): void {
            Route::get('/workspace/settings', [WorkspaceSettingController::class, 'show']);
            Route::put('/workspace/settings', [WorkspaceSettingController::class, 'update']);
        });

        // Wait list management (admin)
        Route::middleware('throttle:api')->group(function (): void {
            Route::get('/waitlist', [WaitlistController::class, 'index']);
            Route::get('/waitlist/stats', [WaitlistController::class, 'stats']);
            Route::get('/waitlist/export', [WaitlistController::class, 'export']);
            Route::patch('/waitlist/status', [WaitlistController::class, 'updateStatus']);
            Route::delete('/waitlist', [WaitlistController::class, 'destroy']);
        });

        // Invitations
        Route::middleware('throttle:invitations')->group(function (): void {
            Route::get('/invitations/received', [InvitationController::class, 'received']);
            Route::get('/invitations/{invitation:uuid}', [InvitationController::class, 'show']);
            Route::delete('/invitations/{invitation:uuid}', [InvitationController::class, 'destroy']);
            Route::post('/invitations/{invitation:token}/accept', [InvitationController::class, 'accept']);
            Route::post('/invitations/{invitation:token}/decline', [InvitationController::class, 'decline']);
            Route::post('/invitations/{invitation:uuid}/resend', [InvitationController::class, 'resend']);
        });
    });
});
