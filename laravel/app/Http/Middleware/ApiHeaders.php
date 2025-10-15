<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add API-specific headers
        $response->headers->set('X-API-Version', 'v1');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // CORS headers (if needed)
        if ($request->getMethod() === 'OPTIONS') {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }

        return $response;
    }
}
