<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Broadcasting authentication routes (for WebSocket private channels)
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// API Version 1 routes
Route::prefix('v1')->group(base_path('routes/api/v1.php'));
