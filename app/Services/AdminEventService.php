<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventStatusEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminEventService
{
    /**
     * Get all events for superadmin review (with optional status filter).
     */
    public function getAllEventsForReview(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 50);

        $query = Event::with(['organizer', 'category', 'divisions']);

        if ($status) {
            $query->where('status', strtoupper($status));
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Approve event and transition to PUBLISHED.
     */
    public function approveEvent(User $admin, Event $event): Event
    {
        $event->update([
            'status' => EventStatusEnum::PUBLISHED,
            'rejection_reason' => null,
        ]);

        return $event->fresh(['organizer', 'category', 'divisions']);
    }

    /**
     * Reject event with official reason note.
     */
    public function rejectEvent(User $admin, Event $event, string $reason): Event
    {
        $event->update([
            'status' => EventStatusEnum::REJECTED,
            'rejection_reason' => $reason,
        ]);

        return $event->fresh(['organizer', 'category', 'divisions']);
    }
}
