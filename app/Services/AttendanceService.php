<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RegistrationStatusEnum;
use App\Exceptions\AlreadyCheckedInException;
use App\Exceptions\InvalidQrSignatureException;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttendanceService
{
    /**
     * Process day-H attendee check-in via QR payload or ticket code.
     *
     * @return array{ticket: Ticket, certificate: Certificate}
     * @throws InvalidQrSignatureException
     * @throws AlreadyCheckedInException
     */
    public function checkIn(User $scannerUser, ?string $qrPayload = null, ?string $fallbackTicketCode = null): array
    {
        $ticketCode = $fallbackTicketCode;

        // 1. Cryptographic HMAC Signature Verification (Anti-Tampering)
        if (! empty($qrPayload)) {
            $ticketCode = $this->verifyQrPayloadSignature($qrPayload);
        }

        if (! $ticketCode) {
            throw new InvalidQrSignatureException('Kode tiket atau payload QR Code tidak dapat dikenali.');
        }

        // 2. Concurrency-safe database transaction with pessimistic locking
        return DB::transaction(function () use ($scannerUser, $ticketCode) {
            /** @var Ticket|null $ticket */
            $ticket = Ticket::with(['registration.user', 'registration.event'])
                ->where('ticket_code', $ticketCode)
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                throw new NotFoundHttpException("Tiket dengan kode '{$ticketCode}' tidak terdaftar pada sistem.");
            }

            // 3. IDOR Authorization Check: Scanner must be event organizer or Superadmin
            $event = $ticket->registration->event;
            if (! $scannerUser->isSuperAdmin() && $event->organizer_id !== $scannerUser->id) {
                throw new AccessDeniedHttpException('Akses ditolak: Anda bukan panitia penyelenggara dari kegiatan ini.');
            }

            // 4. Anti-Double Check-In Guard
            if ($ticket->is_checked_in) {
                $checkedAt = $ticket->checked_in_at ? $ticket->checked_in_at->format('d M Y H:i:s') : 'sebelumnya';
                throw new AlreadyCheckedInException(
                    "Peringatan: Tiket {$ticket->ticket_code} telah dipindai sebelumnya pada {$checkedAt} WIB."
                );
            }

            // 5. Registration status check
            if ($ticket->registration->status === RegistrationStatusEnum::CANCELLED) {
                throw new HttpException(400, 'Pendaftaran untuk tiket ini telah dibatalkan oleh mahasiswa.');
            }

            // 6. Update ticket check-in status
            $ticket->update([
                'is_checked_in' => true,
                'checked_in_at' => now(),
                'checked_in_by' => $scannerUser->id,
            ]);

            // 7. Update registration status to ATTENDED
            $ticket->registration->update([
                'status' => RegistrationStatusEnum::ATTENDED,
            ]);

            // 8. Automated E-Certificate Issuance
            $certificate = Certificate::firstOrCreate(
                ['registration_id' => $ticket->registration_id],
                [
                    'id' => (string) Str::uuid(),
                    'certificate_number' => 'CERT/UII/' . date('Y') . '/' . strtoupper(Str::random(8)),
                    'verify_hash' => hash('sha256', "{$ticket->registration_id}|{$ticket->registration->user_id}|" . microtime(true)),
                    'issued_at' => now(),
                ]
            );

            return [
                'ticket' => $ticket->fresh(['checkedInBy', 'registration.user', 'registration.event']),
                'certificate' => $certificate->fresh(['registration.user', 'registration.event.category']),
            ];
        });
    }

    /**
     * Get paginated attendance list & summary statistics for organizer event.
     *
     * @param array<string, mixed> $filters
     * @return array{paginator: LengthAwarePaginator, stats: array<string, mixed>}
     */
    public function getEventAttendance(User $user, string $eventId, array $filters, int $perPage = 15): array
    {
        /** @var Event $event */
        $event = Event::findOrFail($eventId);

        if (! $user->isSuperAdmin() && $event->organizer_id !== $user->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki wewenang untuk melihat data presensi kegiatan ini.');
        }

        $baseQuery = Ticket::with(['registration.user', 'registration.event', 'checkedInBy'])
            ->whereHas('registration', fn ($q) => $q->where('event_id', $eventId));

        // Calculate summary statistics
        $totalRegistered = (clone $baseQuery)->count();
        $totalAttended = (clone $baseQuery)->where('is_checked_in', true)->count();
        $attendanceRate = $totalRegistered > 0 ? round(($totalAttended / $totalRegistered) * 100, 1) : 0.0;

        // Apply filters
        $query = clone $baseQuery;

        if (isset($filters['is_checked_in']) && $filters['is_checked_in'] !== '') {
            $isCheckedIn = filter_var($filters['is_checked_in'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_checked_in', $isCheckedIn);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('registration.user', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderByDesc('checked_in_at')->orderBy('ticket_code')->paginate(min(max(1, $perPage), 50));

        return [
            'paginator' => $paginator,
            'stats' => [
                'total_registered' => $totalRegistered,
                'total_attended' => $totalAttended,
                'total_absent' => $totalRegistered - $totalAttended,
                'attendance_rate_percent' => $attendanceRate,
            ],
        ];
    }

    /**
     * Publicly verify certificate validity by hash.
     */
    public function verifyCertificate(string $hash): Certificate
    {
        /** @var Certificate|null $certificate */
        $certificate = Certificate::with(['registration.user', 'registration.event.category'])
            ->where('verify_hash', $hash)
            ->first();

        if (! $certificate) {
            throw new NotFoundHttpException('Sertifikat dengan kode verifikasi tersebut tidak ditemukan atau tidak valid.');
        }

        return $certificate;
    }

    /**
     * Get paginated earned certificates for authenticated student.
     *
     * @return LengthAwarePaginator<Certificate>
     */
    public function getMyCertificates(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Certificate::with(['registration.user', 'registration.event.category'])
            ->whereHas('registration', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('issued_at')
            ->paginate(min(max(1, $perPage), 50));
    }

    /**
     * Decodes and cryptographically verifies the QR payload using timing-safe hash_equals().
     *
     * @throws InvalidQrSignatureException
     */
    protected function verifyQrPayloadSignature(string $qrPayload): string
    {
        $decodedJson = base64_decode($qrPayload, true);
        if (! $decodedJson) {
            throw new InvalidQrSignatureException('Format payload QR Code tidak valid (bukan Base64 terenkode).');
        }

        $payload = json_decode($decodedJson, true);
        if (! is_array($payload) || ! isset($payload['ticket_code'], $payload['user_id'], $payload['event_id'], $payload['sig'])) {
            throw new InvalidQrSignatureException('Struktur payload QR Code tidak lengkap atau rusak.');
        }

        $expectedSignature = hash_hmac(
            'sha256',
            "{$payload['ticket_code']}|{$payload['user_id']}|{$payload['event_id']}",
            config('app.key')
        );

        // Timing-safe comparison to prevent timing attacks
        if (! hash_equals($expectedSignature, (string) $payload['sig'])) {
            throw new InvalidQrSignatureException(
                'Tanda tangan digital QR Code tidak valid. Tiket dicurigai palsu atau telah dimanipulasi.'
            );
        }

        return (string) $payload['ticket_code'];
    }
}
