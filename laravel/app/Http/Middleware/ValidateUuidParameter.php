<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ValidateUuidParameter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$parameters): Response
    {
        // Default parameters to check if none specified
        $parametersToCheck = empty($parameters) ? ['uuid'] : $parameters;

        foreach ($parametersToCheck as $parameter) {
            $value = $request->route($parameter);

            if ($value && ! Str::isUuid($value)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid UUID format',
                    'error' => "The {$parameter} parameter must be a valid UUID",
                    'error_code' => 'INVALID_UUID_FORMAT',
                ], 400);
            }
        }

        return $next($request);
    }
}
