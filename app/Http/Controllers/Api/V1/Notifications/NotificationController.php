<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Enums\RegistrationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Notifications\NotificationLogResource;
use App\Jobs\Notifications\SendEventReminderJob;
use App\Models\Event;
use App\Models\Registration;
use App\Services\Notifications\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Get paginated notification delivery logs and summary for an event (Organizer/DPM only).
     */
    public function eventLogs(Request $request, string $id, NotificationService $notificationService): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('view', $event);

        $perPage = (int) $request->query('per_page', 15);
        $channel = $request->query('channel');
        $status = $request->query('status');

        $result = $notificationService->getEventNotificationLogs($event, $perPage, $channel, $status);
        $paginator = $result['logs'];

        return $this->successResponse([
            'summary' => $result['summary'],
            'notifications' => NotificationLogResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'Log riwayat notifikasi kegiatan berhasil diambil.');
    }

    /**
     * Trigger manual broadcast reminders for registered participants (Organizer/DPM only).
     */
    public function broadcastReminders(Request $request, string $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('view', $event);

        $reminderType = (string) $request->input('reminder_type', 'REMINDER_H1');
        if (! in_array($reminderType, ['REMINDER_H1', 'REMINDER_H3', 'REMINDER_MANUAL'], true)) {
            $reminderType = 'REMINDER_H1';
        }

        $registrations = Registration::query()
            ->where('event_id', $event->id)
            ->where('status', RegistrationStatusEnum::REGISTERED)
            ->get();

        $dispatched = 0;
        foreach ($registrations as $reg) {
            SendEventReminderJob::dispatch($reg->id, $reminderType);
            $dispatched++;
        }

        return $this->successResponse([
            'event_id' => $event->id,
            'reminder_type' => $reminderType,
            'dispatched_count' => $dispatched,
        ], "Pengingat berhasil dijadwalkan ke dalam antrean untuk {$dispatched} peserta terdaftar.");
    }
}
