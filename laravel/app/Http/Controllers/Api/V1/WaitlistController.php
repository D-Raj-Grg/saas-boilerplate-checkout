<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Jobs\CreateWaitlistUserAccountJob;
use App\Jobs\SendWebhookJob;
use App\Models\Waitlist;
use App\Services\WaitlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WaitlistController extends BaseApiController
{
    public function __construct(
        private WaitlistService $waitListService
    ) {}

    /**
     * Join the wait list (public endpoint).
     */
    public function join(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['nullable', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:255'],
                'metadata' => ['nullable', 'array'],
                'metadata.*' => ['nullable', 'string', 'max:1000'],
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    'Validation failed',
                    422,
                    $validator->errors()->toArray()
                );
            }

            $waitList = $this->waitListService->join($validator->validated());

            if (! $waitList->getAttribute('is_existing')) {
                if (app()->environment('production')) {
                    SendWebhookJob::dispatch(
                        url: config('webhooks.urls.waitlist_joined'),
                        payload: [
                            'first_name' => $waitList->first_name,
                            'last_name' => $waitList->last_name,
                            'email' => $waitList->email,
                            'date' => $waitList->created_at?->format('d-m-Y H:i:s'),
                        ]
                    );
                }

                // Schedule automatic user account creation after 1 hour
                // This creates a user with "Hello There" as name, random password,
                // and sends email with login credentials (not in testing)
                if (! app()->environment('testing')) {
                    CreateWaitlistUserAccountJob::dispatch($waitList)->delay(now()->addHour());
                }
            }

            return $this->createdResponse([
                'uuid' => $waitList->uuid,
                'email' => $waitList->email,
                'full_name' => $waitList->full_name,
                'status' => $waitList->status,
                'joined_at' => $waitList->created_at?->toISOString(),
            ], 'Successfully joined the wait list');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to join wait list. Please try again.');
        }
    }

    /**
     * Get all wait list entries (admin endpoint).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Waitlist::class);

        $filters = $request->only(['status', 'search', 'from_date', 'to_date']);
        $entries = $this->waitListService->getAll($filters);

        return $this->successResponse([
            'entries' => $entries->map(function (Waitlist $entry) {
                return [
                    'uuid' => $entry->uuid,
                    'first_name' => $entry->first_name,
                    'last_name' => $entry->last_name,
                    'full_name' => $entry->full_name,
                    'email' => $entry->email,
                    'status' => $entry->status,
                    'metadata' => $entry->metadata,
                    'joined_at' => $entry->created_at?->toISOString(),
                    'contacted_at' => $entry->contacted_at?->toISOString(),
                    'converted_at' => $entry->converted_at?->toISOString(),
                ];
            })->values(),
            'stats' => $this->waitListService->getStats(),
        ], 'Wait list entries retrieved successfully');
    }

    /**
     * Get wait list statistics (admin endpoint).
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Waitlist::class);

        return $this->successResponse(
            $this->waitListService->getStats(),
            'Wait list statistics retrieved successfully'
        );
    }

    /**
     * Export wait list to CSV (admin endpoint).
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Waitlist::class);

        $filters = $request->only(['status', 'search', 'from_date', 'to_date']);
        $csv = $this->waitListService->exportToCsv($filters);

        return response()->json([
            'success' => true,
            'data' => [
                'csv' => $csv,
                'filename' => 'waitlist_'.now()->format('Y-m-d_H-i-s').'.csv',
            ],
            'message' => 'Wait list exported successfully',
        ]);
    }

    /**
     * Update entry status (admin endpoint).
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $this->authorize('update', Waitlist::class);

        $validator = Validator::make($request->all(), [
            'uuids' => ['required', 'array', 'min:1'],
            'uuids.*' => ['required', 'string', 'exists:waitlists,uuid'],
            'action' => ['required', 'string', 'in:contact,convert'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()->toArray()
            );
        }

        $data = $validator->validated();

        try {
            $updated = match ($data['action']) {
                'contact' => $this->waitListService->markAsContacted($data['uuids']),
                'convert' => $this->waitListService->markAsConverted($data['uuids']),
                default => throw new \InvalidArgumentException('Invalid action: '.$data['action']),
            };

            $message = match ($data['action']) {
                'contact' => "Marked {$updated} entries as contacted",
                'convert' => "Marked {$updated} entries as converted",
                default => '',
            };

            return $this->successResponse([
                'updated_count' => $updated,
            ], $message);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update entries. Please try again.');
        }
    }

    /**
     * Delete wait list entries (admin endpoint).
     */
    public function destroy(Request $request): JsonResponse
    {
        $this->authorize('delete', Waitlist::class);

        $validator = Validator::make($request->all(), [
            'uuids' => ['required', 'array', 'min:1'],
            'uuids.*' => ['required', 'string', 'exists:waitlists,uuid'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()->toArray()
            );
        }

        try {
            $deleted = $this->waitListService->delete($validator->validated()['uuids']);

            return $this->successResponse([
                'deleted_count' => $deleted,
            ], "Deleted {$deleted} wait list entries");

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete entries. Please try again.');
        }
    }
}
