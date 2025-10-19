<?php

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

// Scribe will automatically add documentation routes at /docs
// - /docs (HTML documentation)
// - /docs.postman (Postman collection)
// - /docs.openapi (OpenAPI spec)

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
