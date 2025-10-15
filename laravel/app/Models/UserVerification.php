<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'identifier',
        'code',
        'token',
        'expires_at',
        'verified_at',
        'attempts',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Constants for verification types.
     */
    public const TYPE_PASSWORD_RESET = 'password_reset';

    public const TYPE_TWO_FACTOR = 'two_factor';

    public const TYPE_EMAIL_VERIFY = 'email_verify';

    public const TYPE_PHONE_VERIFY = 'phone_verify';

    /**
     * Constants for channels.
     */
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_AUTHENTICATOR = 'authenticator';

    /**
     * Get the user that owns the verification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the verification has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the verification is valid.
     */
    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->verified_at;
    }

    /**
     * Check if max attempts reached.
     */
    public function maxAttemptsReached(): bool
    {
        return $this->attempts >= 3;
    }

    /**
     * Increment attempts.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Mark as verified.
     */
    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    /**
     * Scope to get valid verifications.
     */
    public function scopeValid($query)
    {
        return $query->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 3);
    }

    /**
     * Scope to get by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Clean up expired verifications.
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<', now()->subHours(24))->delete();
    }
}
