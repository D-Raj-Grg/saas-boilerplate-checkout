<?php

namespace App\Services;

use App\Models\Waitlist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class WaitlistService
{
    /**
     * Add a new entry to the wait list.
     *
     * @param  array<string, mixed>  $data
     */
    public function join(array $data): Waitlist
    {
        // Check if email already exists
        $existing = Waitlist::where('email', $data['email'])->first();

        if ($existing) {
            // Update existing entry with new data if provided
            $existing->update([
                'first_name' => $data['first_name'] ?? $existing->first_name,
                'last_name' => $data['last_name'] ?? $existing->last_name,
                'metadata' => array_merge($existing->metadata ?? [], $data['metadata'] ?? []),
            ]);

            Log::info('Wait list entry updated', [
                'email' => $existing->email,
                'uuid' => $existing->uuid,
            ]);

            // add is_existing flag to the model
            $existing->setAttribute('is_existing', true);

            return $existing;
        }

        // Create new entry
        $waitList = Waitlist::create([
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'metadata' => $data['metadata'] ?? null,
        ]);

        Log::info('New wait list entry created', [
            'email' => $waitList->email,
            'uuid' => $waitList->uuid,
        ]);

        // Dispatch email notification job if needed
        // \App\Jobs\SendWaitlistWelcomeEmail::dispatch($waitList);

        // add is_existing flag to the model
        $waitList->setAttribute('is_existing', false);

        return $waitList;
    }

    /**
     * Get all wait list entries with optional filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Waitlist>
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Waitlist::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get wait list statistics.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return [
            'total' => Waitlist::count(),
            'pending' => Waitlist::pending()->count(),
            'contacted' => Waitlist::contacted()->count(),
            'converted' => Waitlist::converted()->count(),
            'today' => Waitlist::whereDate('created_at', today())->count(),
            'this_week' => Waitlist::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
            'this_month' => Waitlist::whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])->count(),
        ];
    }

    /**
     * Export wait list entries to CSV.
     *
     * @param  array<string, mixed>  $filters
     */
    public function exportToCsv(array $filters = []): string
    {
        $entries = $this->getAll($filters);

        $csv = "Email,First Name,Last Name,Status,Joined Date\n";

        foreach ($entries as $entry) {
            /** @var Waitlist $entry */
            $csv .= sprintf(
                "%s,%s,%s,%s,%s\n",
                $entry->email,
                $entry->first_name ?? '',
                $entry->last_name ?? '',
                $entry->status,
                $entry->created_at?->format('Y-m-d H:i:s') ?? ''
            );
        }

        return $csv;
    }

    /**
     * Mark multiple entries as contacted.
     *
     * @param  array<string>  $uuids
     */
    public function markAsContacted(array $uuids): int
    {
        return Waitlist::whereIn('uuid', $uuids)
            ->where('status', 'pending')
            ->update([
                'status' => 'contacted',
                'contacted_at' => now(),
            ]);
    }

    /**
     * Mark multiple entries as converted.
     *
     * @param  array<string>  $uuids
     */
    public function markAsConverted(array $uuids): int
    {
        return Waitlist::whereIn('uuid', $uuids)
            ->whereIn('status', ['pending', 'contacted'])
            ->update([
                'status' => 'converted',
                'converted_at' => now(),
            ]);
    }

    /**
     * Delete wait list entries.
     *
     * @param  array<string>  $uuids
     */
    public function delete(array $uuids): int
    {
        return Waitlist::whereIn('uuid', $uuids)->delete();
    }
}
