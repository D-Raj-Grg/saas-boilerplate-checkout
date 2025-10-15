<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ConnectionToken extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'workspace_id',
        'redirect_url',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public static function generate(int $userId, int $workspaceId, string $redirectUrl, int $expiryMinutes = 10): self
    {
        return self::create([
            'token' => Str::random(64),
            'user_id' => $userId,
            'workspace_id' => $workspaceId,
            'redirect_url' => $redirectUrl,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    public function isValid(): bool
    {
        return ! $this->used && $this->expires_at->isFuture();
    }

    public function markAsUsed(): void
    {
        $this->update(['used' => true]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function findValidToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    public static function cleanup(): int
    {
        return self::where('expires_at', '<', now()->subHour())->delete();
    }
}
