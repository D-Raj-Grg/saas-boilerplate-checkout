<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\EmailVerification\VerifyEmailRequest;
use App\Jobs\SendEmailVerificationJob;
use App\Models\User;
use App\Models\UserVerification;
use App\Services\VerificationService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationController extends BaseApiController
{
    private VerificationService $verificationService;

    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Verify email with token.
     */
    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        try {

            $verification = UserVerification::where('token', $request->token)
                ->where('type', UserVerification::TYPE_EMAIL_VERIFY)
                ->first();

            if (! $verification) {
                return $this->errorResponse('Invalid verification token', 404);
            }

            if ($verification->verified_at) {
                return $this->errorResponse('Token already used', 400);
            }

            if ($verification->isExpired()) {
                return $this->errorResponse('Token has expired', 400);
            }

            $user = User::find($verification->user_id);
            if (! $user) {
                return $this->errorResponse('User not found', 404);
            }

            if ($user->email_verified_at) {
                return $this->errorResponse('Email already verified', 400);
            }

            // Verify the email
            $user->email_verified_at = Carbon::now();
            $user->save();

            // Mark this token as verified
            $verification->markAsVerified();

            // Mark all other pending email verification tokens for this user as verified
            // This ensures any other emails they received will show as "already verified"
            UserVerification::where('user_id', $user->id)
                ->where('type', UserVerification::TYPE_EMAIL_VERIFY)
                ->whereNull('verified_at')
                ->where('id', '!=', $verification->id)
                ->update(['verified_at' => Carbon::now()]);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified' => true,
                    'email_verified_at' => $user->email_verified_at,
                ],
            ], 'Email verified successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation errors (returns 422)
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Not authorized to perform this action');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }

    /**
     * Resend verification email.
     */
    public function resend(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();

            if ($user->email_verified_at) {
                return $this->errorResponse('Email already verified', 400);
            }

            // Rate limiting: 1 request per minute per user
            $key = 'verify-email:resend:'.$user->id;
            if (RateLimiter::tooManyAttempts($key, 1)) {
                $seconds = RateLimiter::availableIn($key);

                return $this->errorResponse('Too many requests. Please try again in '.$seconds.' seconds.', 429);
            }

            RateLimiter::hit($key, 60); // 60 seconds

            // Create new verification with 48-hour expiry
            $verification = $this->verificationService->createEmailVerification($user, 48 * 60);

            // Send verification email
            SendEmailVerificationJob::dispatch($user, $verification);

            return $this->successResponse(null, 'Verification email sent');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation errors (returns 422)
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Not authorized to perform this action');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }

    /**
     * Get email verification status.
     */
    public function status(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();

            return $this->successResponse([
                'email_verified' => (bool) $user->email_verified_at,
                'email_verified_at' => $user->email_verified_at,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation errors (returns 422)
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Not authorized to perform this action');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }
}
