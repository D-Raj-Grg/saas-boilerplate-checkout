<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register API-specific services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Authentication endpoints
        RateLimiter::for('auth', function (Request $request) {
            $config = config('rate-limiting.limits.auth');

            return Limit::perMinutes($config['decay_minutes'], $config['attempts'])
                ->by($request->ip());
        });

        // Password reset with stricter limits
        RateLimiter::for('password-reset', function (Request $request) {
            $config = config('rate-limiting.limits.password-reset');

            return Limit::perMinutes($config['decay_minutes'], $config['attempts'])
                ->by($request->input('email') ?: $request->ip());
        });

        // General API endpoints with tier support
        RateLimiter::for('api', function (Request $request) {
            $config = config('rate-limiting.limits.api');
            $limit = $this->getLimitForUser($request->user(), $config['attempts']);

            return Limit::perMinutes($config['decay_minutes'], $limit)
                ->by($this->getRateLimitKey($request));
        });

        // Invitation operations
        RateLimiter::for('invitations', function (Request $request) {
            $config = config('rate-limiting.limits.invitations', ['attempts' => 20, 'decay_minutes' => 1]);
            $limit = $this->getLimitForUser($request->user(), $config['attempts']);

            return Limit::perMinutes($config['decay_minutes'], $limit)
                ->by($this->getRateLimitKey($request));
        });

        // Workspace settings
        RateLimiter::for('workspace-settings', function (Request $request) {
            $config = config('rate-limiting.limits.workspace_settings');
            $limit = $this->getLimitForUser($request->user(), $config['attempts']);

            return Limit::perMinutes($config['decay_minutes'], $limit)
                ->by($this->getRateLimitKey($request));
        });
    }

    /**
     * Get the rate limit for a user based on their organization's plan.
     */
    protected function getLimitForUser(?User $user, int $baseLimit): int
    {
        if (! $user) {
            return $baseLimit;
        }

        // Get the user's organization
        $organization = $user->currentOrganization;
        if (! $organization) {
            return $baseLimit;
        }

        // Get absolute rate limit from organization's plan
        return $organization->getRateLimit('api');
    }

    /**
     * Get the rate limit key for the request.
     */
    protected function getRateLimitKey(Request $request, string $prefix = ''): string
    {
        $user = $request->user();

        if ($user && $user->currentOrganization) {
            // Rate limit by organization
            $key = 'org:'.$user->currentOrganization->id;
        } elseif ($user) {
            // Rate limit by user
            $key = 'user:'.$user->id;
        } else {
            // Rate limit by IP for unauthenticated requests
            $key = 'ip:'.$request->ip();
        }

        return $prefix ? "{$prefix}:{$key}" : $key;
    }
}
