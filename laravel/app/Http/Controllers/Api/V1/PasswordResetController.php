<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use App\Models\UserVerification;
use App\Services\VerificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends BaseApiController
{
    public function __construct(
        private VerificationService $verificationService
    ) {}

    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $email = $request->input('email');

            // Always return success to prevent email enumeration
            $response = $this->successResponse([
                'expires_in_minutes' => 15,
            ], 'If the email exists, a reset code has been sent.');

            // Check if user exists
            $user = User::where('email', $email)->first();
            if (! $user) {
                return $response;
            }

            // Check rate limit
            if (! $this->verificationService->canRequestVerificationByIdentifier($email, UserVerification::TYPE_PASSWORD_RESET)) {
                return $response;
            }

            // Create verification
            $verification = $this->verificationService->createVerification(
                $user,
                UserVerification::TYPE_PASSWORD_RESET,
                $email
            );

            // Send email via queue job
            $frontendUrl = config('app.frontend_url', config('app.url'));
            \App\Jobs\SendPasswordResetEmail::dispatch($verification, $frontendUrl);

            return $response;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation errors (returns 422)
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Not authorized to perform this action');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }

    public function verifyToken(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Find the verification
            $verification = UserVerification::where('token', $request->input('token'))
                ->where('type', UserVerification::TYPE_PASSWORD_RESET)
                ->where('expires_at', '>', now())
                ->first();

            if (! $verification) {
                throw ValidationException::withMessages([
                    'token' => ['The provided token is invalid or has expired.'],
                ]);
            }

            // Mark as verified
            $verification->markAsVerified();

            return $this->successResponse(null, 'Token verified successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation errors (returns 422)
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Not authorized to perform this action');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }

    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Find the verification
            $verification = UserVerification::where('token', $request->input('token'))
                ->where('type', UserVerification::TYPE_PASSWORD_RESET)
                ->where('expires_at', '>', now())
                ->whereNotNull('verified_at') // Token must be verified first
                ->first();

            if (! $verification) {
                throw ValidationException::withMessages([
                    'token' => ['The provided token is invalid or has expired.'],
                ]);
            }

            // Check if token was verified within the last 30 minutes
            if ($verification->verified_at < now()->subMinutes(30)) {
                throw ValidationException::withMessages([
                    'token' => ['The provided token is invalid or has expired.'],
                ]);
            }

            // Update the user's password
            /** @var User $user */
            $user = $verification->user;
            $user->update([
                'password' => Hash::make($request->input('password')),
            ]);

            // Mark the verification as expired instead of deleting
            $verification->update(['expires_at' => now()]);

            // Revoke all tokens for security
            $user->tokens()->delete();

            return $this->successResponse(null, 'Password has been reset successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation errors (returns 422)
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Not authorized to perform this action');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }
}
