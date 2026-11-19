<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\EventStatusEnum;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrganizerEventService
{
    /**
     * Get paginated events managed by this organizer.
     */
    public function getMyEvents(User $user, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 50);

        $query = Event::with(['category', 'organizer']);

        if (! $user->isSuperAdmin()) {
            $query->where('organizer_id', $user->id);
        }

        if ($status) {
            $query->where('status', strtoupper($status));
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new draft event.
     *
     * @param array<string, mixed> $data
     */
    public function createDraft(User $user, array $data): Event
    {
        return DB::transaction(function () use ($user, $data) {
            $title = strip_tags((string) $data['title']);
            $baseSlug = Str::slug($title);
            $slug = $baseSlug . '-' . Str::lower(Str::random(5));

            $event = Event::create([
                'id' => (string) Str::uuid(),
                'organizer_id' => $user->id,
                'category_id' => $data['category_id'],
                'title' => $title,
                'slug' => $slug,
                'description' => ! empty($data['description']) ? strip_tags((string) $data['description']) : null,
                'activity_type' => $data['activity_type'],
                'poster_url' => $data['poster_url'],
                'quota_total' => $data['quota_total'],
                'quota_remaining' => $data['quota_total'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'location' => strip_tags((string) $data['location']),
                'status' => EventStatusEnum::DRAFT,
                'auto_post_ig' => $data['auto_post_ig'] ?? true,
            ]);

            // Save divisions if oprec
            if (! empty($data['divisions']) && is_array($data['divisions'])) {
                foreach ($data['divisions'] as $div) {
                    EventDivision::create([
                        'event_id' => $event->id,
                        'name' => $div['name'],
                        'quota' => $div['quota'],
                        'description' => $div['description'] ?? null,
                    ]);
                }
            }

            return $event->load(['category', 'divisions']);
        });
    }

    /**
     * Update an existing draft or rejected event.
     *
     * @param array<string, mixed> $data
     */
    public function updateEvent(User $user, Event $event, array $data): Event
    {
        return DB::transaction(function () use ($event, $data) {
            $updateData = [];

            if (isset($data['title'])) {
                $safeTitle = strip_tags((string) $data['title']);
                $updateData['title'] = $safeTitle;
                // Update slug only if title changed
                if ($safeTitle !== $event->title) {
                    $updateData['slug'] = Str::slug($safeTitle) . '-' . Str::lower(Str::random(5));
                }
            }

            foreach (['category_id', 'activity_type', 'poster_url', 'start_time', 'end_time', 'auto_post_ig'] as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (isset($data['description'])) {
                $updateData['description'] = ! empty($data['description']) ? strip_tags((string) $data['description']) : null;
            }

            if (isset($data['location'])) {
                $updateData['location'] = strip_tags((string) $data['location']);
            }

            if (isset($data['quota_total'])) {
                $difference = $data['quota_total'] - $event->quota_total;
                $updateData['quota_total'] = $data['quota_total'];
                $updateData['quota_remaining'] = max(0, $event->quota_remaining + $difference);
            }

            $event->update($updateData);

            // Update divisions if provided
            if (isset($data['divisions']) && is_array($data['divisions'])) {
                // Delete removed or recreate divisions
                $event->divisions()->delete();
                foreach ($data['divisions'] as $div) {
                    EventDivision::create([
                        'event_id' => $event->id,
                        'name' => strip_tags((string) $div['name']),
                        'quota' => $div['quota'],
                        'description' => ! empty($div['description']) ? strip_tags((string) $div['description']) : null,
                    ]);
                }
            }

            return $event->fresh(['category', 'divisions']);
        });
    }

    /**
     * Submit event for Super Admin approval.
     */
    public function submitForApproval(User $user, Event $event): Event
    {
        $event->update([
            'status' => EventStatusEnum::PENDING_APPROVAL,
            'rejection_reason' => null, // Reset previous rejection note if any
        ]);

        return $event->fresh(['category', 'divisions']);
    }

    /**
     * Delete a draft event.
     */
    public function deleteDraft(User $user, Event $event): void
    {
        $event->delete();
    }
}
