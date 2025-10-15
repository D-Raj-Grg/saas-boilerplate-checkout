<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserVerification;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VerificationService
{
    /**
     * Create a new verification code for a user.
     */
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function createVerification(
        User $user,
        string $type,
        string $identifier,
        string $channel = UserVerification::CHANNEL_EMAIL,
        ?array $metadata = null
    ): UserVerification {
        // Invalidate any existing verifications of the same type
        $this->invalidateExistingVerifications($user, $type);

        // Generate a secure token for URL-based verification (72 characters)
        $token = $this->generateSecureToken();

        // For password reset, we don't need a code
        $code = $type === UserVerification::TYPE_PASSWORD_RESET ? null : $this->generateCode();

        // Create the verification
        return UserVerification::create([
            'user_id' => $user->id,
            'type' => $type,
            'channel' => $channel,
            'identifier' => $identifier,
            'code' => $code,
            'token' => $token,
            'expires_at' => Carbon::now()->addMinutes($this->getExpiryMinutes($type)),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Check if identifier can request a new verification.
     */
    public function canRequestVerificationByIdentifier(string $identifier, string $type): bool
    {
        // Check if identifier has requested too many verifications recently
        $recentCount = UserVerification::where('identifier', $identifier)
            ->where('type', $type)
            ->where('created_at', '>', Carbon::now()->subHour())
            ->count();

        return $recentCount < 3;
    }

    /**
     * Invalidate existing verifications.
     */
    private function invalidateExistingVerifications(User $user, string $type): void
    {
        UserVerification::where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->update(['expires_at' => Carbon::now()]);
    }

    /**
     * Generate a 6-digit code.
     */
    private function generateCode(): string
    {
        return Str::padLeft((string) random_int(100000, 999999), 6, '0');
    }

    /**
     * Generate a secure token for password reset (72+ characters).
     */
    private function generateSecureToken(): string
    {
        // Generate 72 characters of random alphanumeric string
        // Using multiple sources for better randomness
        $token = '';

        // Add 48 characters from Str::random (uses random_bytes internally)
        $token .= Str::random(48);

        // Add 24 more characters using a different approach
        $token .= bin2hex(random_bytes(12));

        // Ensure we have exactly 72 characters and mix them up
        return Str::substr(str_shuffle($token), 0, 64);
    }

    /**
     * Create email verification for a user.
     */
    public function createEmailVerification(User $user, int $expiryMinutes = 2880): UserVerification
    {
        // Generate a secure token for URL-based verification
        $token = $this->generateSecureToken();

        // Create the verification with custom expiry (default 48 hours)
        return UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_EMAIL_VERIFY,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'code' => null, // No code needed for link-based verification
            'token' => $token,
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
            'metadata' => null,
        ]);
    }

    /**
     * Get expiry minutes based on type.
     */
    private function getExpiryMinutes(string $type): int
    {
        return match ($type) {
            UserVerification::TYPE_PASSWORD_RESET => 60, // 1 hour for password reset
            UserVerification::TYPE_TWO_FACTOR => 5,
            UserVerification::TYPE_EMAIL_VERIFY => 60,
            UserVerification::TYPE_PHONE_VERIFY => 10,
            default => 15,
        };
    }
}
