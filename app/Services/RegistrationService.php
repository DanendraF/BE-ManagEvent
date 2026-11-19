<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventStatusEnum;
use App\Enums\RegistrationStatusEnum;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegistrationService
{
    /**
     * Register student as event participant (Concurrency-safe & Anti-Double Entry).
     *
     * @param array<string, mixed> $customAnswers
     * @throws ConflictHttpException|HttpException
     */
    public function registerParticipant(User $user, string $eventId, array $customAnswers = []): Ticket
    {
        // 1. Application-level check: Verify user is not already actively registered
        $alreadyRegistered = Registration::where('user_id', $user->id)
            ->where('event_id', $eventId)
            ->where('status', '!=', RegistrationStatusEnum::CANCELLED->value)
            ->exists();

        if ($alreadyRegistered) {
            throw new ConflictHttpException('Anda sudah terdaftar pada kegiatan ini.');
        }

        // 2. Database transaction with pessimistic lock on event quota
        return DB::transaction(function () use ($user, $eventId, $customAnswers) {
            /** @var Event $event */
            $event = Event::where('id', $eventId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($event->status !== EventStatusEnum::PUBLISHED) {
                throw new HttpException(400, 'Pendaftaran ditutup: Kegiatan belum dipublikasikan atau sudah selesai.');
            }

            if ($event->quota_remaining <= 0) {
                throw new HttpException(400, 'Kuota pendaftaran untuk kegiatan ini telah habis.');
            }

            // Decrement remaining quota
            $event->decrement('quota_remaining');

            // Create Registration record (Backed by DB-level Partial Unique Index)
            $registration = Registration::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'event_id' => $event->id,
                'registration_type' => 'PARTICIPANT',
                'custom_answers' => ! empty($customAnswers) ? $customAnswers : null,
                'status' => RegistrationStatusEnum::REGISTERED,
                'registered_at' => now(),
            ]);

            // Generate Cryptographically Signed Ticket & QR Payload
            $ticketCode = 'TKT-' . date('Y') . '-' . strtoupper(Str::random(8));
            $rawSecret = (string) config('app.key');

            $signature = hash_hmac('sha256', "{$ticketCode}|{$user->id}|{$event->id}", $rawSecret);
            $qrPayload = base64_encode(json_encode([
                'ticket_code' => $ticketCode,
                'reg_id' => $registration->id,
                'user_id' => $user->id,
                'event_id' => $event->id,
                'sig' => $signature,
            ]));

            $ticket = Ticket::create([
                'id' => (string) Str::uuid(),
                'registration_id' => $registration->id,
                'ticket_code' => $ticketCode,
                'qr_payload' => $qrPayload,
                'is_checked_in' => false,
            ]);

            return $ticket->load(['registration.event.category', 'registration.user']);
        });
    }

    /**
     * Get paginated active tickets of authenticated student.
     */
    public function getMyTickets(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 50);

        return Ticket::whereHas('registration', fn ($q) => $q->where('user_id', $user->id))
            ->with(['registration.event.category', 'registration.user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get single ticket by ticket code (IDOR Protected).
     *
     * @throws NotFoundHttpException|AccessDeniedHttpException
     */
    public function getTicketByCode(User $user, string $ticketCode): Ticket
    {
        $ticket = Ticket::where('ticket_code', $ticketCode)
            ->with(['registration.event.category', 'registration.user'])
            ->first();

        if (! $ticket) {
            throw new NotFoundHttpException('Tiket dengan kode referensi tersebut tidak ditemukan.');
        }

        // IDOR Protection: Student can only view their own ticket
        if (! $user->isSuperAdmin() && ! $user->isOrganizer() && $ticket->registration->user_id !== $user->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki wewenang untuk mengakses tiket ini.');
        }

        return $ticket;
    }

    /**
     * Cancel an active participant registration and restore event quota.
     *
     * @throws NotFoundHttpException|AccessDeniedHttpException|HttpException
     */
    public function cancelRegistration(User $user, string $ticketCode): Ticket
    {
        return DB::transaction(function () use ($user, $ticketCode) {
            $ticket = Ticket::where('ticket_code', $ticketCode)
                ->with(['registration.event', 'registration.user'])
                ->first();

            if (! $ticket) {
                throw new NotFoundHttpException('Tiket tidak ditemukan.');
            }

            if ($ticket->registration->user_id !== $user->id) {
                throw new AccessDeniedHttpException('Anda tidak memiliki izin untuk membatalkan tiket ini.');
            }

            if ($ticket->is_checked_in) {
                throw new HttpException(400, 'Tiket yang sudah dipindai (hadir) tidak dapat dibatalkan.');
            }

            if ($ticket->registration->status === RegistrationStatusEnum::CANCELLED) {
                throw new HttpException(400, 'Pendaftaran tiket ini sudah pernah dibatalkan.');
            }

            // Update registration status to CANCELLED
            $ticket->registration->update([
                'status' => RegistrationStatusEnum::CANCELLED,
            ]);

            // Restore quota to the event
            /** @var Event $event */
            $event = Event::where('id', $ticket->registration->event_id)
                ->lockForUpdate()
                ->first();

            if ($event) {
                $event->increment('quota_remaining');
            }

            return $ticket->fresh(['registration.event', 'registration.user']);
        });
    }

    /**
     * Get event participant registrations for organizer or superadmin.
     */
    public function getEventRegistrations(User $user, string $eventId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $event = Event::findOrFail($eventId);

        if (! $user->isSuperAdmin() && $event->organizer_id !== $user->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki wewenang untuk melihat rekap pendaftar acara ini.');
        }

        $perPage = min(max($perPage, 1), 50);

        $query = Registration::where('event_id', $eventId)
            ->with(['user', 'ticket']);

        if ($status) {
            $query->where('status', strtoupper($status));
        }

        return $query->orderBy('registered_at', 'desc')->paginate($perPage);
    }
}
