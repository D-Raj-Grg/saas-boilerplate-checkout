<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add web-specific headers
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN'); // Less restrictive for web
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Powered-By', config('app.name').' API v'.config('app.version', '1.0'));

        return $response;
    }
}
