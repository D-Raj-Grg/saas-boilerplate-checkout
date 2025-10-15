<?php

use App\Exceptions\ABTestingException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

if (! function_exists('getContextualNotFoundMessage')) {
    /**
     * Get a contextual "not found" message based on the API endpoint
     */
    function getContextualNotFoundMessage(Request $request): string
    {
        $path = $request->path();

        // Match API patterns to provide specific messages
        if (str_contains($path, '/workspaces/')) {
            return 'Workspace not found. It may have been deleted or you may not have access to it.';
        }

        if (str_contains($path, '/organizations/')) {
            return 'Organization not found. It may have been deleted or you may not have access to it.';
        }

        if (str_contains($path, '/invitations/')) {
            return 'Invitation not found or has expired.';
        }

        // Default fallback
        return 'Resource not found';
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-specific middleware configuration
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Add API-specific middleware
        $middleware->api([
            \App\Http\Middleware\ApiHeaders::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'workspace.access' => \App\Http\Middleware\EnsureUserBelongsToWorkspace::class,
            'organization.access' => \App\Http\Middleware\EnsureUserBelongsToOrganization::class,
            'uuid.validate' => \App\Http\Middleware\ValidateUuidParameter::class,
            'context' => \App\Http\Middleware\EnsureValidUserContext::class,
            'rate.limit.headers' => \App\Http\Middleware\RateLimitHeaders::class,
        ]);

        // Web-specific middleware
        $middleware->web([
            \App\Http\Middleware\WebHeaders::class,
        ]);

        $middleware->group('web-js', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\WebHeaders::class,
        ]);

        // Keep full web middleware stack including CSRF protection for web routes
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Authentication exceptions
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error_code' => 'UNAUTHENTICATED',
                ], Response::HTTP_UNAUTHORIZED);
            }
        });

        // Application exceptions
        $exceptions->render(function (ABTestingException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $e->render();
            }
        });

        // HTTP exceptions (404, 403, etc.)
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $statusCode = $e->getStatusCode();
                $message = match ($statusCode) {
                    404 => getContextualNotFoundMessage($request),
                    403 => 'Access forbidden',
                    405 => 'Method not allowed',
                    429 => 'Rate limit exceeded. Please wait before making more requests.',
                    default => $e->getMessage() ?: 'An error occurred',
                };

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_code' => 'HTTP_'.$statusCode,
                ], $statusCode);
            }
            // For web routes, let Laravel handle normally (could show error pages)
        });

        // Validation exceptions
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'error_code' => 'VALIDATION_FAILED',
                    'errors' => $e->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            // For web routes, let Laravel handle normally (redirect back with errors)
        });

        // Generic exception handler for API routes
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Don't handle exceptions already handled above
                if ($e instanceof AuthenticationException ||
                    $e instanceof HttpException ||
                    $e instanceof ValidationException ||
                    $e instanceof ABTestingException) {
                    return null;
                }

                $isDebug = config('app.debug');
                $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

                return response()->json([
                    'success' => false,
                    'message' => $isDebug ? $e->getMessage() : 'Internal server error',
                    'error_code' => 'INTERNAL_ERROR',
                    ...$isDebug ? [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5)->toArray(),
                    ] : [],
                ], $statusCode);
            }
        });
    })->create();
