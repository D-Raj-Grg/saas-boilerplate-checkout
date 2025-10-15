<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use Exception;
use PostHog\PostHog;

class PostHogService
{
    /**
     * Dispatch event to PostHog.
     *
     * @param  array<string, mixed>  $eventData
     */
    private function dispatchEvent(array $eventData): void
    {
        // Skip PostHog in testing environment
        if (config('app.env') !== 'production') {
            return;
        }

        try {
            // Check if PostHog class exists
            if (! class_exists('PostHog\PostHog')) {
                return;
            }

            // Get PostHog configuration
            $config = config('services.posthog');
            if (! $config || ! $config['enabled'] || empty($config['api_key'])) {
                return;
            }

            // Initialize PostHog with configuration every time to ensure it's properly set up
            PostHog::init($config['api_key'], [
                'host' => $config['host'],
            ]);

            // Prepare event data
            $distinctId = $eventData['distinctId'] ?? 'unknown';
            $event = $eventData['event'] ?? 'unknown_event';
            $properties = $eventData['properties'] ?? [];

            // Add standard properties
            $properties = array_merge($properties, [
                'environment' => config('app.env'),
                '$lib' => 'php-laravel',
                'source' => 'backend',
            ]);

            // Add timestamp if not present
            if (! isset($properties['$timestamp'])) {
                $properties['$timestamp'] = now()->toISOString();
            }

            // Send event to PostHog
            PostHog::capture([
                'distinctId' => $distinctId,
                'event' => $event,
                'properties' => $properties,
            ]);

        } catch (Exception $e) {
            // Silently fail to prevent breaking the application
            \Log::warning('PostHog tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track user signup event.
     */
    public function trackUserSignup(User $user): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => 'user_signed_up',
                'properties' => [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'registration_date' => $user->created_at?->toISOString(),
                    'signup_timestamp' => now()->toISOString(),
                    'email_verified' => (bool) $user->email_verified_at,
                ],
            ]);

            $this->identifyUser($user);
        } catch (Exception $e) {
            // Silently fail to prevent breaking user registration
            \Log::warning('PostHog user signup tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track user login event.
     */
    public function trackUserLogin(User $user): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => 'user_logged_in',
                'properties' => [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                    'login_timestamp' => now()->toISOString(),
                    'email_verified' => (bool) $user->email_verified_at,
                    'current_organization_id' => $user->current_organization_id,
                    'current_workspace_id' => $user->current_workspace_id,
                ],
            ]);

            $this->identifyUser($user);
        } catch (Exception $e) {
            \Log::warning('PostHog user login tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track email verification event.
     */
    public function trackEmailVerification(User $user): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => 'email_verified',
                'properties' => [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'user_email' => $user->email,
                    'verification_timestamp' => now()->toISOString(),
                    'email_verified_at' => $user->email_verified_at?->toISOString(),
                ],
            ]);
        } catch (Exception $e) {
            \Log::warning('PostHog email verification tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track organization creation event.
     */
    public function trackOrganizationCreated(User $user, Organization $organization): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => 'organization_created',
                'properties' => [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'organization_id' => $organization->id,
                    'organization_uuid' => $organization->uuid,
                    'organization_name' => $organization->name,
                    'creation_timestamp' => now()->toISOString(),
                ],
            ]);
        } catch (Exception $e) {
            \Log::warning('PostHog organization creation tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track workspace creation event.
     */
    public function trackWorkspaceCreated(User $user, Workspace $workspace): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => 'workspace_created',
                'properties' => [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'workspace_id' => $workspace->id,
                    'workspace_uuid' => $workspace->uuid,
                    'workspace_name' => $workspace->name,
                    'organization_id' => $workspace->organization_id,
                    'creation_timestamp' => now()->toISOString(),
                ],
            ]);
        } catch (Exception $e) {
            \Log::warning('PostHog workspace creation tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track custom event.
     *
     * @param  array<string, mixed>  $properties
     */
    public function trackCustomEvent(User $user, string $eventName, array $properties = []): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => $eventName,
                'properties' => array_merge($properties, [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'user_email' => $user->email,
                    'event_timestamp' => now()->toISOString(),
                ]),
            ]);
        } catch (Exception $e) {
            \Log::warning('PostHog custom event tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track anonymous event (for non-authenticated users).
     *
     * @param  array<string, mixed>  $properties
     */
    public function trackAnonymousEvent(string $visitorId, string $eventName, array $properties = []): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => $visitorId,
                'event' => $eventName,
                'properties' => array_merge($properties, [
                    'visitor_id' => $visitorId,
                    'event_timestamp' => now()->toISOString(),
                ]),
            ]);
        } catch (Exception $e) {
            \Log::warning('PostHog anonymous event tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Identify user in PostHog.
     */
    public function identifyUser(User $user): void
    {
        try {
            // Skip PostHog in testing environment
            if (config('app.env') !== 'production') {
                return;
            }

            // Check if PostHog class exists
            if (! class_exists('PostHog\PostHog')) {
                return;
            }

            // Get PostHog configuration
            $config = config('services.posthog');
            if (! $config || ! $config['enabled'] || empty($config['api_key'])) {
                return;
            }

            // Initialize PostHog with configuration
            PostHog::init($config['api_key'], [
                'host' => $config['host'],
            ]);

            // Identify user with properties
            PostHog::identify([
                'distinctId' => (string) $user->id,
                'properties' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email_verified' => (bool) $user->email_verified_at,
                    'created_at' => $user->created_at?->toISOString(),
                    'current_organization_id' => $user->current_organization_id,
                    'current_workspace_id' => $user->current_workspace_id,
                    'organization_count' => $user->organization_count,
                    'workspace_count' => $user->workspace_count,
                    'owned_organization_count' => $user->owned_organization_count,
                ],
            ]);
        } catch (Exception $e) {
            \Log::warning('PostHog user identification failed: '.$e->getMessage());
        }
    }

    /**
     * Track page view event.
     *
     * @param  array<string, mixed>  $properties
     */
    public function trackPageView(User $user, string $pageUrl, array $properties = []): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => 'page_viewed',
                'properties' => array_merge($properties, [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'page_url' => $pageUrl,
                    'page_title' => $properties['page_title'] ?? null,
                    'referrer' => $properties['referrer'] ?? null,
                    'viewport_timestamp' => now()->toISOString(),
                ]),
            ]);
        } catch (Exception $e) {
            \Log::warning('PostHog page view tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track user session context change (organization/workspace switching).
     */
    public function trackSessionContextChange(User $user, string $contextType, Organization|Workspace $contextEntity): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => 'session_context_changed',
                'properties' => [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'context_type' => $contextType,
                    'context_id' => $contextEntity->id,
                    'context_uuid' => $contextEntity->uuid,
                    'context_name' => $contextEntity->name,
                    'change_timestamp' => now()->toISOString(),
                ],
            ]);
        } catch (Exception $e) {
            \Log::warning('PostHog session context change tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track feature usage.
     *
     * @param  array<string, mixed>  $properties
     */
    public function trackFeatureUsage(User $user, string $featureName, array $properties = []): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => 'feature_used',
                'properties' => array_merge($properties, [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'feature_name' => $featureName,
                    'usage_timestamp' => now()->toISOString(),
                ]),
            ]);
        } catch (Exception $e) {
            \Log::warning('PostHog feature usage tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track error events.
     *
     * @param  array<string, mixed>  $properties
     */
    public function trackError(User $user, string $errorType, string $errorMessage, array $properties = []): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => 'error_occurred',
                'properties' => array_merge($properties, [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'error_type' => $errorType,
                    'error_message' => $errorMessage,
                    'error_timestamp' => now()->toISOString(),
                ]),
            ]);
        } catch (Exception $e) {
            \Log::warning('PostHog error tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Track user properties update.
     *
     * @param  array<string, mixed>  $updatedProperties
     */
    public function trackUserPropertiesUpdate(User $user, array $updatedProperties): void
    {
        try {
            $this->dispatchEvent([
                'distinctId' => (string) $user->id,
                'event' => 'user_properties_updated',
                'properties' => [
                    'user_id' => $user->id,
                    'user_uuid' => $user->uuid,
                    'updated_properties' => $updatedProperties,
                    'update_timestamp' => now()->toISOString(),
                ],
            ]);

            // Also update user identification with new properties
            $this->identifyUser($user);
        } catch (Exception $e) {
            \Log::warning('PostHog user properties update tracking failed: '.$e->getMessage());
        }
    }
}
