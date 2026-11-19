<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannelEnum;
use App\Mail\EventReminderMail;
use App\Mail\EventTicketMail;
use App\Models\Event;
use App\Models\NotificationLog;
use App\Models\Registration;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationService
{
    /**
     * Send e-ticket issuance notification with resilient WhatsApp -> Email fallback.
     *
     * @return array{status: string, channel?: string, fallback?: bool, error?: string}
     */
    public function sendTicketIssuedNotification(Registration $registration): array
    {
        $registration->loadMissing(['user', 'event.category', 'ticket']);

        $user = $registration->user;
        $event = $registration->event;
        $ticket = $registration->ticket;

        if (! $user || ! $event) {
            return ['status' => 'FAILED', 'error' => 'Missing registration user or event.'];
        }

        // 1. Idempotency Check: Prevent duplicate ticket sending
        $alreadySent = NotificationLog::query()
            ->where('registration_id', $registration->id)
            ->where('delivery_status', 'SENT')
            ->where('response_payload', 'LIKE', '%"type":"TICKET_ISSUED"%')
            ->first();

        if ($alreadySent) {
            return [
                'status' => 'ALREADY_SENT',
                'channel' => is_string($alreadySent->channel) ? $alreadySent->channel : $alreadySent->channel->value,
            ];
        }

        $phone = $user->phone_number;
        $email = $user->email;
        $ticketCode = $ticket?->ticket_code ?? '-';
        $startTime = $event->start_time?->format('d M Y, H:i') ?? '-';
        $location = $event->location ?? 'Daring / Online';

        $waMessage = "🎟️ *E-TIKET RESMI UII EVENT MANAGEMENT*\n\n"
            . "Halo *{$user->name}*!\n"
            . "Pendaftaran Anda untuk kegiatan *{$event->title}* telah berhasil dikonfirmasi.\n\n"
            . "• *Kode Tiket:* `{$ticketCode}`\n"
            . "• *Jadwal Acara:* {$startTime} WIB\n"
            . "• *Lokasi / Venue:* {$location}\n\n"
            . "Mohon simpan kode tiket ini atau tunjukkan QR Code pada meja registrasi panitia saat hari-H untuk presensi dan penerbitan e-sertifikat.\n\n"
            . "_Sistem Informasi Manajemen Acara Universitas Islam Indonesia_";

        // 2. Attempt WhatsApp Gateway Delivery
        $waSent = $this->attemptWhatsAppDelivery($event->id, $registration->id, $phone, $waMessage, 'TICKET_ISSUED');
        if ($waSent) {
            return ['status' => 'SENT', 'channel' => NotificationChannelEnum::WHATSAPP->value, 'fallback' => false];
        }

        // 3. Resilient Automatic Fallback to SMTP Email UII
        $emailSent = $this->attemptEmailDelivery(
            $event->id,
            $registration->id,
            $email,
            new EventTicketMail($registration),
            'TICKET_ISSUED',
            'Fallback otomatis dari WhatsApp ke SMTP Email Kampus UII'
        );

        if ($emailSent) {
            return ['status' => 'SENT', 'channel' => NotificationChannelEnum::EMAIL->value, 'fallback' => true];
        }

        return ['status' => 'FAILED', 'error' => 'Both WhatsApp and Email deliveries failed.'];
    }

    /**
     * Send event reminder notification (H-1 or H-3 Hours) with resilient WhatsApp -> Email fallback.
     *
     * @return array{status: string, channel?: string, fallback?: bool, error?: string}
     */
    public function sendEventReminderNotification(Registration $registration, string $reminderType = 'REMINDER_H1'): array
    {
        $registration->loadMissing(['user', 'event.category', 'ticket']);

        $user = $registration->user;
        $event = $registration->event;
        $ticket = $registration->ticket;

        if (! $user || ! $event) {
            return ['status' => 'FAILED', 'error' => 'Missing registration user or event.'];
        }

        // 1. Idempotency Check: Prevent duplicate reminders for same reminder type
        $alreadySent = NotificationLog::query()
            ->where('registration_id', $registration->id)
            ->where('delivery_status', 'SENT')
            ->where('response_payload', 'LIKE', '%"type":"' . $reminderType . '"%')
            ->first();

        if ($alreadySent) {
            return [
                'status' => 'ALREADY_SENT',
                'channel' => is_string($alreadySent->channel) ? $alreadySent->channel : $alreadySent->channel->value,
            ];
        }

        $phone = $user->phone_number;
        $email = $user->email;
        $ticketCode = $ticket?->ticket_code ?? '-';
        $startTime = $event->start_time?->format('d M Y, H:i') ?? '-';
        $location = $event->location ?? 'Daring / Online';

        $headerText = $reminderType === 'REMINDER_H3' ? '⏰ *[H-3 Jam Menuju Acara]*' : '📅 *[Pengingat H-1 Acara]*';

        $waMessage = "{$headerText} *PENGINGAT ACARA KAMPUS UII*\n\n"
            . "Halo *{$user->name}*!\n"
            . "Kegiatan yang Anda daftarkan akan segera dimulai:\n\n"
            . "• *Nama Acara:* {$event->title}\n"
            . "• *Jadwal Mulai:* {$startTime} WIB\n"
            . "• *Lokasi:* {$location}\n"
            . "• *Kode Tiket Anda:* `{$ticketCode}`\n\n"
            . "Pastikan Anda hadir tepat waktu dan siapkan tiket QR Anda untuk pemindaian di meja panitia.\n\n"
            . "_UII Event Management Platform_";

        // 2. Attempt WhatsApp Gateway Delivery
        $waSent = $this->attemptWhatsAppDelivery($event->id, $registration->id, $phone, $waMessage, $reminderType);
        if ($waSent) {
            return ['status' => 'SENT', 'channel' => NotificationChannelEnum::WHATSAPP->value, 'fallback' => false];
        }

        // 3. Resilient Automatic Fallback to SMTP Email UII
        $emailSent = $this->attemptEmailDelivery(
            $event->id,
            $registration->id,
            $email,
            new EventReminderMail($registration, $reminderType),
            $reminderType,
            'Fallback otomatis dari WhatsApp ke SMTP Email Pengingat UII'
        );

        if ($emailSent) {
            return ['status' => 'SENT', 'channel' => NotificationChannelEnum::EMAIL->value, 'fallback' => true];
        }

        return ['status' => 'FAILED', 'error' => 'Both WhatsApp and Email reminder deliveries failed.'];
    }

    /**
     * Attempt WhatsApp sending with timeout and logging.
     */
    protected function attemptWhatsAppDelivery(
        string $eventId,
        string $registrationId,
        string $phone,
        string $message,
        string $type
    ): bool {
        $endpoint = (string) config('services.whatsapp.endpoint', 'https://api.fonnte.com/send');
        $token = (string) config('services.whatsapp.token', '');
        $timeout = (int) config('services.whatsapp.timeout', 10);

        if (empty($token)) {
            NotificationLog::create([
                'event_id' => $eventId,
                'registration_id' => $registrationId,
                'channel' => NotificationChannelEnum::WHATSAPP,
                'recipient' => $phone,
                'delivery_status' => 'FAILED',
                'response_payload' => json_encode([
                    'type' => $type,
                    'error' => 'WHATSAPP_TOKEN_MISSING: Gateway token is empty, triggering automatic fallback.',
                ]),
            ]);

            return false;
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['Authorization' => $token])
                ->post($endpoint, [
                    'target' => $phone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                NotificationLog::create([
                    'event_id' => $eventId,
                    'registration_id' => $registrationId,
                    'channel' => NotificationChannelEnum::WHATSAPP,
                    'recipient' => $phone,
                    'delivery_status' => 'SENT',
                    'response_payload' => json_encode([
                        'type' => $type,
                        'gateway_response' => $response->json(),
                    ]),
                ]);

                return true;
            }

            NotificationLog::create([
                'event_id' => $eventId,
                'registration_id' => $registrationId,
                'channel' => NotificationChannelEnum::WHATSAPP,
                'recipient' => $phone,
                'delivery_status' => 'FAILED',
                'response_payload' => json_encode([
                    'type' => $type,
                    'status_code' => $response->status(),
                    'error' => $response->body(),
                ]),
            ]);

            return false;
        } catch (Throwable $e) {
            NotificationLog::create([
                'event_id' => $eventId,
                'registration_id' => $registrationId,
                'channel' => NotificationChannelEnum::WHATSAPP,
                'recipient' => $phone,
                'delivery_status' => 'FAILED',
                'response_payload' => json_encode([
                    'type' => $type,
                    'exception' => $e->getMessage(),
                ]),
            ]);

            Log::warning("WhatsApp notification delivery failed: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Attempt SMTP Email sending with error logging.
     */
    protected function attemptEmailDelivery(
        string $eventId,
        string $registrationId,
        string $email,
        \Illuminate\Mail\Mailable $mailable,
        string $type,
        string $notes = ''
    ): bool {
        try {
            Mail::to($email)->send($mailable);

            NotificationLog::create([
                'event_id' => $eventId,
                'registration_id' => $registrationId,
                'channel' => NotificationChannelEnum::EMAIL,
                'recipient' => $email,
                'delivery_status' => 'SENT',
                'response_payload' => json_encode([
                    'type' => $type,
                    'notes' => $notes,
                ]),
            ]);

            return true;
        } catch (Throwable $e) {
            NotificationLog::create([
                'event_id' => $eventId,
                'registration_id' => $registrationId,
                'channel' => NotificationChannelEnum::EMAIL,
                'recipient' => $email,
                'delivery_status' => 'FAILED',
                'response_payload' => json_encode([
                    'type' => $type,
                    'exception' => $e->getMessage(),
                ]),
            ]);

            Log::error("SMTP Email notification delivery failed: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Get paginated notification logs and summary metrics for an event.
     *
     * @return array{summary: array<string, int>, logs: LengthAwarePaginator}
     */
    public function getEventNotificationLogs(
        Event $event,
        int $perPage = 15,
        ?string $channel = null,
        ?string $status = null
    ): array {
        $perPage = min(max($perPage, 1), 50);

        $query = NotificationLog::query()
            ->with(['registration.user'])
            ->where('event_id', $event->id)
            ->latest();

        if ($channel) {
            $query->where('channel', strtoupper($channel));
        }

        if ($status) {
            $query->where('delivery_status', strtoupper($status));
        }

        $logs = $query->paginate($perPage);

        // Aggregate summary metrics
        $allEventLogs = NotificationLog::query()->where('event_id', $event->id)->get();

        $summary = [
            'total_notifications' => $allEventLogs->count(),
            'sent_count' => $allEventLogs->where('delivery_status', 'SENT')->count(),
            'failed_count' => $allEventLogs->where('delivery_status', 'FAILED')->count(),
            'whatsapp_count' => $allEventLogs->where('channel', NotificationChannelEnum::WHATSAPP)->count(),
            'email_count' => $allEventLogs->where('channel', NotificationChannelEnum::EMAIL)->count(),
        ];

        return [
            'summary' => $summary,
            'logs' => $logs,
        ];
    }
}
