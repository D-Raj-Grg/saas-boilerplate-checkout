<?php

use App\Http\Controllers\Api\V1\JSGeneratorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Homepage - redirect to documentation
Route::get('/', function () {
    return redirect('/status');
});

// Serve screenshots with proper caching and security
Route::get('/storage/screenshots/{filename}', function ($filename) {
    $path = storage_path('app/public/screenshots/'.$filename);

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => mime_content_type($path),
        'Cache-Control' => 'public, max-age=31536000', // 1 year cache
        'ETag' => md5_file($path),
    ]);
})->where('filename', '.*');

// API Documentation routes
Route::get('/docs', function () {
    return response()->json([
        'message' => config('app.name').' API Documentation',
        'endpoints' => [
            'scribe_docs' => url('/docs/postman'),
            'health' => url('/api/v1/health'),
        ],
    ]);
})->name('docs');

// Scribe will automatically add documentation routes at /docs

// JavaScript SDK endpoint (public, no rate limiting)
Route::middleware('web-js')->get('/sdk/ws/{workspace_uuid}.js', [JSGeneratorController::class, 'serveWorkspaceJS'])
    ->where('workspace_uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
    ->withoutMiddleware('web');

// Health check for web (different from API health check)
Route::get('/status', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name').' API',
        // 'environment' => app()->environment(),
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
    ]);
})->name('status');
