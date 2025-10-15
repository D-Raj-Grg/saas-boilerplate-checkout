<?php

use App\Services\WebSocketListenerService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('custom.{sessionId}', function ($user, $sessionId) {
    // Only allow authenticated users
    if (! $user) {
        return false;
    }

    // Store the user-session mapping for permission checks during broadcasting
    // This also prevents session hijacking by refusing to overwrite existing mappings
    $webSocketService = app(WebSocketListenerService::class);
    $connected = $webSocketService->connectUser($sessionId, $user->id);

    // Only allow connection if session is available or already owned by this user
    return $connected;
});
