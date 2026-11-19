<?php

declare(strict_types=1);

namespace App\Services\Registrations;

use App\Enums\ActivityTypeEnum;
use App\Enums\EventStatusEnum;
use App\Enums\RegistrationStatusEnum;
use App\Enums\SelectionStatusEnum;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class RecruitmentService
{
    /**
     * Submit open recruitment application for student with hardened CV upload.
     *
     * @param array<string, mixed> $data
     */
    public function applyForRecruitment(User $user, string $eventId, array $data, UploadedFile $cvFile): Registration
    {
        // 1. Application-level check: Verify student is not actively registered
        $alreadyApplied = Registration::where('user_id', $user->id)
            ->where('event_id', $eventId)
            ->where('status', '!=', RegistrationStatusEnum::CANCELLED->value)
            ->exists();

        if ($alreadyApplied) {
            throw new ConflictHttpException('Anda sudah mendaftar pada kegiatan / open recruitment ini.');
        }

        // 2. Validate magic bytes of uploaded CV file (Hardening: Must be real PDF)
        $this->validatePdfMagicBytes($cvFile);

        // 3. Database transaction with pessimistic locking on Event
        return DB::transaction(function () use ($user, $eventId, $data, $cvFile) {
            /** @var Event $event */
            $event = Event::with('divisions')
                ->where('id', $eventId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($event->status !== EventStatusEnum::PUBLISHED) {
                throw new HttpException(400, 'Pendaftaran ditutup: Kegiatan open recruitment belum dipublikasikan atau sudah berakhir.');
            }

            if ($event->activity_type !== ActivityTypeEnum::COMMITTEE_RECRUITMENT) {
                throw new HttpException(400, 'Kegiatan ini merupakan jalur peserta umum, bukan open recruitment panitia.');
            }

            if ($event->quota_remaining <= 0) {
                throw new HttpException(400, 'Kuota pendaftaran open recruitment panitia untuk kegiatan ini telah habis.');
            }

            // 4. Validate division preferences against event's configured divisions
            $validDivisionNames = $event->divisions->pluck('name')->all();

            if (! in_array($data['division_pref_1'], $validDivisionNames, true)) {
                throw new UnprocessableEntityHttpException(
                    "Pilihan divisi utama '{$data['division_pref_1']}' tidak tersedia pada kepanitiaan ini."
                );
            }

            if (! empty($data['division_pref_2']) && ! in_array($data['division_pref_2'], $validDivisionNames, true)) {
                throw new UnprocessableEntityHttpException(
                    "Pilihan divisi kedua '{$data['division_pref_2']}' tidak tersedia pada kepanitiaan ini."
                );
            }

            // 5. Secure storage of CV file (UUID-based sanitized file path)
            $storedCvPath = $this->storeCvSecurely($event->id, $cvFile);

            // 6. Decrement remaining quota
            $event->decrement('quota_remaining');

            // 7. Create Registration record (Backed by DB Partial Unique Index)
            $sanitizedAnswers = $this->sanitizeCustomAnswers($data['custom_answers'] ?? null);
            $registration = Registration::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'event_id' => $event->id,
                'registration_type' => 'COMMITTEE',
                'custom_answers' => $sanitizedAnswers,
                'division_pref_1' => strip_tags((string) $data['division_pref_1']),
                'division_pref_2' => ! empty($data['division_pref_2']) ? strip_tags((string) $data['division_pref_2']) : null,
                'cv_file_url' => $storedCvPath,
                'selection_status' => SelectionStatusEnum::SUBMITTED,
                'status' => RegistrationStatusEnum::REGISTERED,
                'registered_at' => now(),
            ]);

            return $registration->load(['user', 'event.divisions']);
        });
    }

    /**
     * Get paginated list of recruitment applications for authenticated student.
     *
     * @return LengthAwarePaginator<Registration>
     */
    public function getMyApplications(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Registration::with(['event.divisions', 'user'])
            ->where('user_id', $user->id)
            ->where('registration_type', 'COMMITTEE')
            ->orderByDesc('registered_at')
            ->paginate(min(max(1, $perPage), 50));
    }

    /**
     * Get paginated applicants for organizer event with filtering.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Registration>
     */
    public function getEventApplicants(User $user, string $eventId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        /** @var Event $event */
        $event = Event::findOrFail($eventId);

        // IDOR Check: Must be organizer of event or Superadmin
        if (! $user->isSuperAdmin() && $event->organizer_id !== $user->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki wewenang untuk melihat data pendaftar kegiatan ini.');
        }

        $query = Registration::with('user')
            ->where('event_id', $eventId)
            ->where('registration_type', 'COMMITTEE');

        // Filter by division
        if (! empty($filters['division'])) {
            $division = $filters['division'];
            $query->where(function ($q) use ($division) {
                $q->where('division_pref_1', $division)
                    ->orWhere('division_pref_2', $division);
            });
        }

        // Filter by selection status
        if (! empty($filters['status'])) {
            $query->where('selection_status', $filters['status']);
        }

        // Filter by student name or NIM
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('registered_at')->paginate(min(max(1, $perPage), 50));
    }

    /**
     * Update recruitment applicant selection status.
     */
    public function updateSelectionStatus(
        User $user,
        string $registrationId,
        SelectionStatusEnum $status,
        ?string $notes = null
    ): Registration {
        /** @var Registration $registration */
        $registration = Registration::with('event')->findOrFail($registrationId);

        // IDOR Check: Organizer ownership or Superadmin
        if (! $user->isSuperAdmin() && $registration->event->organizer_id !== $user->id) {
            throw new AccessDeniedHttpException('Anda tidak memiliki hak akses untuk mengubah status seleksi pendaftar ini.');
        }

        $customAnswers = $registration->custom_answers ?? [];
        if ($notes !== null) {
            $customAnswers['selection_notes'] = $notes;
            $customAnswers['selection_updated_at'] = now()->toISOString();
            $registration->custom_answers = $customAnswers;
        }

        $registration->selection_status = $status;
        $registration->save();

        return $registration->load('user');
    }

    /**
     * Download or stream applicant CV with strict IDOR access control.
     */
    public function streamApplicantCv(User $user, string $registrationId): \Symfony\Component\HttpFoundation\Response
    {
        /** @var Registration $registration */
        $registration = Registration::with('event')->findOrFail($registrationId);

        // IDOR Access Rule:
        // 1. The student who applied
        // 2. The organizer who owns the event
        // 3. Superadmin
        $isApplicant = $registration->user_id === $user->id;
        $isEventOrganizer = $registration->event->organizer_id === $user->id;
        $isSuperAdmin = $user->isSuperAdmin();

        if (! $isApplicant && ! $isEventOrganizer && ! $isSuperAdmin) {
            throw new AccessDeniedHttpException('Akses ditolak: Anda tidak memiliki izin untuk mengunduh atau melihat dokumen CV ini.');
        }

        if (! $registration->cv_file_url || ! Storage::disk('local')->exists($registration->cv_file_url)) {
            throw new NotFoundHttpException('Berkas CV fisik tidak ditemukan di penyimpanan server.');
        }

        $content = Storage::disk('local')->get($registration->cv_file_url);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="CV_' . $registration->user_id . '.pdf"',
        ]);
    }

    /**
     * Validate PDF magic bytes (%PDF) to prevent spoofed/malicious file uploads.
     */
    protected function validatePdfMagicBytes(UploadedFile $file): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if (! $handle) {
            throw new UnprocessableEntityHttpException('Gagal membaca berkas yang diunggah.');
        }

        $header = fread($handle, 4);
        fclose($handle);

        if ($header !== '%PDF') {
            throw new UnprocessableEntityHttpException(
                'Berkas CV ditolak: Header dokumen tidak valid (Bukan file PDF asli). Ekstensi atau format berkas telah dimanipulasi.'
            );
        }
    }

    /**
     * Store CV file in isolated local storage with sanitized UUID filename.
     */
    protected function storeCvSecurely(string $eventId, UploadedFile $file): string
    {
        $randomFileName = Str::uuid()->toString() . '.pdf';
        $directory = 'recruitment_docs/' . $eventId;

        return $file->storeAs($directory, $randomFileName, 'local');
    }

    /**
     * Recursively sanitize custom answers to prevent Stored XSS injection.
     *
     * @param array<string, mixed>|null $answers
     * @return array<string, mixed>|null
     */
    protected function sanitizeCustomAnswers(?array $answers): ?array
    {
        if (empty($answers)) {
            return null;
        }

        $sanitized = [];
        foreach ($answers as $key => $value) {
            $safeKey = strip_tags((string) $key);
            if (is_string($value)) {
                $sanitized[$safeKey] = trim(strip_tags($value));
            } elseif (is_array($value)) {
                $sanitized[$safeKey] = $this->sanitizeCustomAnswers($value);
            } else {
                $sanitized[$safeKey] = $value;
            }
        }

        return $sanitized;
    }
}
