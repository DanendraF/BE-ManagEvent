<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EventStatusEnum;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view the event management details.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->isSuperAdmin() || ($user->isOrganizer() && $event->organizer_id === $user->id);
    }

    /**
     * Determine whether the user can update the event.
     */
    public function update(User $user, Event $event): bool
    {
        // Organizer can only update their own events when status is DRAFT or REJECTED
        if ($user->isOrganizer() && $event->organizer_id === $user->id) {
            return in_array($event->status, [EventStatusEnum::DRAFT, EventStatusEnum::REJECTED], true);
        }

        // Superadmin has master update privilege
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the event.
     */
    public function delete(User $user, Event $event): bool
    {
        // Only draft events can be deleted by their organizer
        return $user->isOrganizer()
            && $event->organizer_id === $user->id
            && $event->status === EventStatusEnum::DRAFT;
    }

    /**
     * Determine whether the organizer can submit the event for approval.
     */
    public function submit(User $user, Event $event): bool
    {
        return $user->isOrganizer()
            && $event->organizer_id === $user->id
            && in_array($event->status, [EventStatusEnum::DRAFT, EventStatusEnum::REJECTED], true);
    }

    /**
     * Determine whether the user can approve the event.
     */
    public function approve(User $user, Event $event): bool
    {
        return $user->isSuperAdmin() && $event->status === EventStatusEnum::PENDING_APPROVAL;
    }

    /**
     * Determine whether the user can reject the event.
     */
    public function reject(User $user, Event $event): bool
    {
        return $user->isSuperAdmin() && $event->status === EventStatusEnum::PENDING_APPROVAL;
    }
}
