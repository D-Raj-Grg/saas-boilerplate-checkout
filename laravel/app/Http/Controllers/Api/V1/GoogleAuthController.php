<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Invitation;
use App\Models\Plan;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\OrganizationService;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends BaseApiController
{
    private OrganizationService $organizationService;

    private InvitationService $invitationService;

    private WorkspaceService $workspaceService;

    public function __construct(
        OrganizationService $organizationService,
        InvitationService $invitationService,
        WorkspaceService $workspaceService
    ) {
        $this->organizationService = $organizationService;
        $this->invitationService = $invitationService;
        $this->workspaceService = $workspaceService;
    }

    /**
     * Redirect to Google OAuth provider.
     */
    public function redirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $provider */
        $provider = Socialite::driver('google');

        return $provider->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback.
     * Creates temporary exchange code and redirects to frontend.
     */
    public function callback(Request $request): \Illuminate\Http\RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\GoogleProvider $provider */
            $provider = Socialite::driver('google');
            $googleUser = $provider->stateless()->user();

            $tokenData = DB::transaction(function () use ($googleUser, $request) {
                // Check if user exists by google_id
                $user = User::where('google_id', $googleUser->getId())->first();

                // If not found by google_id, check by email
                if (! $user) {
                    $user = User::where('email', $googleUser->getEmail())->first();

                    // If user exists with this email, link Google account
                    if ($user) {
                        // SECURITY: Only link Google account if the email is already verified
                        // If email is NOT verified, someone may have maliciously registered with this email
                        if ($user->email_verified_at) {
                            // Safe to link - user has proven ownership of this email
                            $user->update([
                                'google_id' => $googleUser->getId(),
                                'avatar' => $googleUser->getAvatar(),
                            ]);
                        } else {
                            // SECURITY FIX: Email not verified - prevent account takeover
                            // Replace password with random string to invalidate any attacker's password
                            // Verify email since Google has verified it
                            $user->update([
                                'google_id' => $googleUser->getId(),
                                'avatar' => $googleUser->getAvatar(),
                                'password' => Hash::make(Str::random(64)), // Invalidate old password
                                'email_verified_at' => now(), // Google verified the email
                            ]);
                        }
                    }
                }

                // If user still doesn't exist, create new user
                if (! $user) {
                    // Extract name parts
                    $name = $googleUser->getName() ?? 'User';
                    $nameParts = explode(' ', $name, 2);
                    $firstName = $nameParts[0];
                    $lastName = $nameParts[1] ?? '';

                    $user = User::create([
                        'name' => $name,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'password' => Hash::make(Str::random(64)), // Set random password for security
                        'email_verified_at' => now(), // Google emails are pre-verified
                    ]);

                    // Check if there's an invitation token
                    $invitationAccepted = false;

                    if ($request->has('invitation_token')) {
                        $invitation = Invitation::where('token', $request->invitation_token)
                            ->where('email', $googleUser->getEmail())
                            ->where('status', 'pending')
                            ->where('expires_at', '>', now())
                            ->first();

                        if ($invitation) {
                            try {
                                $this->invitationService->acceptInvitation($request->invitation_token, $user);
                                $invitationAccepted = true;
                            } catch (\Exception $e) {
                                \Log::error('Failed to accept invitation during Google OAuth', [
                                    'user_id' => $user->id,
                                    'invitation_token' => $request->invitation_token,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }

                    // Only create default organization if user wasn't invited
                    if (! $invitationAccepted) {
                        $defaultPlanId = Plan::whereSlug(config('constants.default_org_plan_slug'))->value('id') ?? null;

                        /** @var \App\Models\User $user */
                        $organization = $this->organizationService->create($user, [
                            'name' => $name."'s Organization",
                            'workspace_name' => $name."'s Workspace",
                            'plan_id' => $defaultPlanId,
                        ]);

                        $this->workspaceService->create($organization, $user, [
                            'name' => $name."'s Workspace",
                        ]);
                    }
                }

                // Create auth token
                $token = $user->createToken('auth_token')->plainTextToken;

                return [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'email_verified' => true,
                ];
            });

            // Generate secure exchange code (60 seconds TTL)
            $exchangeCode = Str::random(64);

            // Store token data in cache with 60 second expiration
            Cache::put(
                "oauth_exchange:{$exchangeCode}",
                $tokenData,
                now()->addSeconds(60)
            );

            // Redirect to frontend login page with exchange code (NOT the token)
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $callbackUrl = $frontendUrl.'/login';

            $queryParams = http_build_query([
                'code' => $exchangeCode, // Safe to put in URL
                'provider' => 'google', // Optional: to identify OAuth provider
            ]);

            return redirect($callbackUrl.'?'.$queryParams);

        } catch (\Exception $e) {
            \Log::error('Google OAuth error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Redirect to frontend login page with error
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $callbackUrl = $frontendUrl.'/login';

            $queryParams = http_build_query([
                'error' => 'oauth_failed',
                'message' => 'Failed to authenticate with Google. Please try again.',
            ]);

            return redirect($callbackUrl.'?'.$queryParams);
        }
    }

    /**
     * Exchange temporary code for access token.
     */
    public function exchange(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $exchangeCode = $request->input('code');
        $cacheKey = "oauth_exchange:{$exchangeCode}";

        // Get token data from cache
        $tokenData = Cache::get($cacheKey);

        if (! $tokenData) {
            return $this->errorResponse('Invalid or expired exchange code', 400);
        }

        // Delete the code immediately (one-time use)
        Cache::forget($cacheKey);

        return $this->successResponse($tokenData, 'Token exchanged successfully');
    }
}
