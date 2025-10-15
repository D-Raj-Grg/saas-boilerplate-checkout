<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\WebSocketListenerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebSocketController extends BaseApiController
{
    public function __construct(
        private readonly WebSocketListenerService $webSocketService
    ) {}

    /**
     * Mark a session as having active WebSocket listeners
     */
    public function connect(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Invalid session ID provided', 400, $validator->errors());
        }

        $sessionId = $request->input('session_id');
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        // Connect the authenticated user to this session
        $connected = $this->webSocketService->connectUser($sessionId, $user->id);

        if (! $connected) {
            return $this->errorResponse('Session is already owned by another user', 409);
        }

        return $this->successResponse([
            'session_id' => $sessionId,
            'status' => 'connected',
            'expires_at' => $this->webSocketService->getExpirationTime($sessionId),
            'user_id' => $user->id,
        ], 'WebSocket listener registered successfully');
    }

    /**
     * Remove a session from active WebSocket listeners
     */
    public function disconnect(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Invalid session ID provided', 400, $validator->errors());
        }

        $sessionId = $request->input('session_id');
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        // Disconnect the specific user from this session
        $this->webSocketService->disconnectUser($sessionId, $user->id);

        return $this->successResponse([
            'session_id' => $sessionId,
            'status' => 'disconnected',
            'user_id' => $user->id,
        ], 'WebSocket listener removed successfully');
    }
}
