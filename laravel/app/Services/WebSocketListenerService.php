<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class WebSocketListenerService
{
    private const CACHE_TTL = 6 * 60 * 60; // 6 hours in seconds

    /**
     * Connect a user to a session for WebSocket listening
     * Note: Each session can only have one user
     * Security: Prevents session hijacking by refusing to overwrite existing mappings
     */
    public function connectUser(string $sessionId, int $userId): bool
    {
        $cacheKey = $this->getCacheKey($sessionId);
        $existingUserId = Cache::get($cacheKey);

        // If session already exists for a different user, refuse connection
        if ($existingUserId !== null && (string) $existingUserId !== (string) $userId) {
            return false; // Prevent session hijacking
        }

        // Allow connection (new session or same user reconnecting)
        return Cache::put($cacheKey, $userId, self::CACHE_TTL);
    }

    /**
     * Disconnect a user from a session
     */
    public function disconnectUser(string $sessionId, int $userId): bool
    {
        $cacheKey = $this->getCacheKey($sessionId);
        $storedUserId = Cache::get($cacheKey);

        // Only disconnect if this user owns the session
        if ((string) $storedUserId === (string) $userId) {
            return Cache::forget($cacheKey);
        }

        return false;
    }

    /**
     * Remove a session from active WebSocket listeners
     */
    public function disconnect(string $sessionId): bool
    {
        $cacheKey = $this->getCacheKey($sessionId);

        return Cache::forget($cacheKey);
    }

    /**
     * Check if a session has an active WebSocket listener
     */
    public function hasActiveListeners(string $sessionId): bool
    {
        $cacheKey = $this->getCacheKey($sessionId);

        return Cache::has($cacheKey);
    }

    /**
     * Check if we should broadcast an event based on user permissions
     */
    public function shouldBroadcastEvent(string $sessionId): bool
    {
        $userId = $this->getListeningUser($sessionId);

        if (! $userId) {
            return false;
        }

        // Check if the listening user exists
        return User::find($userId) !== null;
    }

    /**
     * Get the user ID listening to a session
     */
    public function getListeningUser(string $sessionId): ?int
    {
        $cacheKey = $this->getCacheKey($sessionId);

        return Cache::get($cacheKey);
    }

    /**
     * Get the expiration time for a session's listeners
     */
    public function getExpirationTime(string $sessionId): ?string
    {
        if (! $this->hasActiveListeners($sessionId)) {
            return null;
        }

        // Since we can't get exact expiry from Laravel Cache, return estimated time
        return now()->addSeconds(self::CACHE_TTL)->toISOString();
    }

    /**
     * Get cache TTL in seconds
     */
    public function getCacheTTL(): int
    {
        return self::CACHE_TTL;
    }

    /**
     * Generate cache key for session
     */
    private function getCacheKey(string $sessionId): string
    {
        return "websocket_session_user_{$sessionId}";
    }
}
