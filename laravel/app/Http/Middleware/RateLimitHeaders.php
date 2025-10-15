<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitHeaders
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    /**
     * Handle an incoming request and add rate limit headers to the response.
     */
    public function handle(Request $request, Closure $next, string $limiterName = 'api'): Response
    {
        $response = $next($request);

        if (! config('rate-limiting.headers.enabled', true)) {
            return $response;
        }

        // Detect the actual limiter being used by checking the route
        $actualLimiter = $this->detectActiveLimiter($request) ?? $limiterName;

        // Get the rate limit key
        $key = $this->resolveRequestSignature($request, $actualLimiter);

        // Get rate limit information
        $maxAttempts = $this->getMaxAttempts($request, $actualLimiter);
        $remainingAttempts = $this->getRemainingAttempts($key, $maxAttempts);
        $retryAfter = $this->getRetryAfter($key);
        $resetTime = $this->getResetTime($key);

        // Add headers to response
        $headers = config('rate-limiting.headers');

        return $response->withHeaders([
            $headers['limit'] => $maxAttempts,
            $headers['remaining'] => max(0, $remainingAttempts),
            $headers['reset'] => $resetTime,
        ] + ($retryAfter > 0 ? [$headers['retry_after'] => $retryAfter] : []));
    }

    /**
     * Resolve the request signature for the rate limiter.
     */
    protected function resolveRequestSignature(Request $request, string $limiterName): string
    {
        $user = $request->user();

        if ($user && $user->currentOrganization) {
            $key = 'org:'.$user->currentOrganization->id;
        } elseif ($user) {
            $key = 'user:'.$user->id;
        } else {
            $key = 'ip:'.$request->ip();
        }

        return $limiterName.':'.$key;
    }

    /**
     * Get the maximum number of attempts for the given limiter.
     */
    protected function getMaxAttempts(Request $request, string $limiterName): int
    {
        $config = config("rate-limiting.limits.{$limiterName}", config('rate-limiting.limits.api'));

        if (is_array($config) && isset($config['attempts'])) {
            $baseLimit = $config['attempts'];
        } else {
            $baseLimit = 60; // fallback
        }

        // Get absolute rate limit from organization plan if user is authenticated
        if ($user = $request->user()) {
            $organization = $user->currentOrganization;
            if ($organization) {
                return $organization->getRateLimit($limiterName);
            }
        }

        return $baseLimit;
    }

    /**
     * Get the number of remaining attempts.
     */
    protected function getRemainingAttempts(string $key, int $maxAttempts): int
    {
        $attempts = (int) Cache::get($key, 0);

        return $maxAttempts - $attempts;
    }

    /**
     * Get the retry after time in seconds.
     */
    protected function getRetryAfter(string $key): int
    {
        $retryAfter = Cache::get($key.':timer');

        if ($retryAfter && $retryAfter instanceof \DateTime) {
            return max(0, $retryAfter->getTimestamp() - time());
        }

        return 0;
    }

    /**
     * Get the reset time as a Unix timestamp.
     */
    protected function getResetTime(string $key): int
    {
        $retryAfter = Cache::get($key.':timer');

        if ($retryAfter && $retryAfter instanceof \DateTime) {
            return $retryAfter->getTimestamp();
        }

        // Default to 1 minute from now
        return time() + 60;
    }

    /**
     * Detect the actual rate limiter being used based on the request path.
     */
    protected function detectActiveLimiter(Request $request): ?string
    {
        $path = $request->path();

        // Map paths to their corresponding rate limiters
        $pathMappings = [
            'api/v1/invitations' => 'invitations',
            'api/v1/analytics' => 'analytics',
        ];

        // Check for exact matches first
        if (isset($pathMappings[$path])) {
            return $pathMappings[$path];
        }

        // Check for pattern matches
        if (str_contains($path, 'invitations')) {
            return 'invitations';
        }

        if (str_contains($path, 'analytics')) {
            return 'analytics';
        }

        // Default to api for everything else
        return 'api';
    }
}
