<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityTypeEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\RegistrationStatusEnum;
use App\Enums\SelectionStatusEnum;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\NotificationLog;
use App\Models\Registration;

class EventReportingService
{
    /**
     * Get comprehensive recap metrics for an event.
     *
     * @return array<string, mixed>
     */
    public function getEventRecap(Event $event): array
    {
        $event->loadMissing(['category', 'organizer', 'divisions']);

        // 1. Participant Registrations & Attendance
        $registrations = Registration::with(['ticket', 'user', 'certificate'])
            ->where('event_id', $event->id)
            ->get();

        $activeParticipants = $registrations->where('registration_type', 'PARTICIPANT')
            ->where('status', '!=', RegistrationStatusEnum::CANCELLED);

        $totalRegistered = $activeParticipants->count();
        $attendedCount = $activeParticipants->filter(fn ($r) => (bool) $r->ticket?->is_checked_in)->count();
        $notAttendedCount = max(0, $totalRegistered - $attendedCount);
        $attendanceRate = $totalRegistered > 0 ? round(($attendedCount / $totalRegistered) * 100, 1) : 0.0;
        $cancelledCount = $registrations->where('status', RegistrationStatusEnum::CANCELLED)->count();

        // 2. Recruitment Applicants Metrics
        $isRecruitment = $event->activity_type === ActivityTypeEnum::COMMITTEE_RECRUITMENT || $event->divisions->isNotEmpty();
        $applicants = $registrations->where('registration_type', 'COMMITTEE');
        $totalApplicants = $applicants->count();

        $recruitmentMetrics = null;
        if ($isRecruitment || $totalApplicants > 0) {
            $recruitmentMetrics = [
                'total_applicants' => $totalApplicants,
                'submitted_count' => $applicants->where('selection_status', SelectionStatusEnum::SUBMITTED)->count(),
                'interview_scheduled_count' => $applicants->where('selection_status', SelectionStatusEnum::INTERVIEW_SCHEDULED)->count(),
                'accepted_count' => $applicants->where('selection_status', SelectionStatusEnum::ACCEPTED)->count(),
                'rejected_count' => $applicants->where('selection_status', SelectionStatusEnum::REJECTED)->count(),
            ];
        }

        // 3. Certificates Issued
        $certificatesCount = Certificate::whereHas('registration', fn ($q) => $q->where('event_id', $event->id))->count();

        // 4. Notification Metrics
        $logs = NotificationLog::where('event_id', $event->id)->get();
        $notifMetrics = [
            'total_logs' => $logs->count(),
            'whatsapp_sent' => $logs->where('channel', NotificationChannelEnum::WHATSAPP)->where('delivery_status', 'SENT')->count(),
            'whatsapp_failed' => $logs->where('channel', NotificationChannelEnum::WHATSAPP)->where('delivery_status', 'FAILED')->count(),
            'email_sent' => $logs->where('channel', NotificationChannelEnum::EMAIL)->where('delivery_status', 'SENT')->count(),
            'email_failed' => $logs->where('channel', NotificationChannelEnum::EMAIL)->where('delivery_status', 'FAILED')->count(),
            'instagram_sent' => $logs->where('channel', NotificationChannelEnum::INSTAGRAM)->where('delivery_status', 'SENT')->count(),
            'instagram_failed' => $logs->where('channel', NotificationChannelEnum::INSTAGRAM)->where('delivery_status', 'FAILED')->count(),
        ];

        return [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'status' => is_string($event->status) ? $event->status : $event->status->value,
                'category' => $event->category?->name,
                'organizer' => $event->organizer?->name,
                'start_time' => $event->start_time?->toIso8601String(),
                'end_time' => $event->end_time?->toIso8601String(),
                'location' => $event->location,
                'quota_total' => $event->quota_total,
                'quota_remaining' => $event->quota_remaining,
            ],
            'participants' => [
                'total_registered' => $totalRegistered,
                'attended_count' => $attendedCount,
                'not_attended_count' => $notAttendedCount,
                'attendance_rate_percent' => $attendanceRate,
                'cancelled_count' => $cancelledCount,
            ],
            'recruitment' => $recruitmentMetrics,
            'certificates' => [
                'total_issued' => $certificatesCount,
            ],
            'notifications' => $notifMetrics,
        ];
    }

    /**
     * Export participant attendance & registration recap to CSV string with UTF-8 BOM.
     */
    public function exportParticipantsCsv(Event $event): string
    {
        $registrations = Registration::with(['user', 'ticket', 'certificate'])
            ->where('event_id', $event->id)
            ->where('registration_type', 'PARTICIPANT')
            ->orderBy('created_at', 'asc')
            ->get();

        $handle = fopen('php://temp', 'r+');
        if (! $handle) {
            return '';
        }

        // Write UTF-8 BOM for Microsoft Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        // Header Row
        fputcsv($handle, [
            'No',
            'Kode Tiket',
            'NIM',
            'Nama Lengkap',
            'Email Kampus',
            'Nomor HP / WhatsApp',
            'Fakultas',
            'Program Studi',
            'Status Registrasi',
            'Waktu Mendaftar',
            'Status Presensi',
            'Waktu Check-In',
            'Nomor E-Sertifikat',
            'Jawaban Tambahan',
        ]);

        $no = 1;
        foreach ($registrations as $reg) {
            $user = $reg->user;
            $ticket = $reg->ticket;
            $cert = $reg->certificate;

            $customStr = '';
            if (! empty($reg->custom_answers)) {
                $customStr = json_encode($reg->custom_answers, JSON_UNESCAPED_UNICODE);
            }

            fputcsv($handle, [
                $no++,
                $ticket?->ticket_code ?? '-',
                $user?->nim ?? '-',
                $user?->name ?? '-',
                $user?->email ?? '-',
                $user?->phone_number ?? '-',
                $user?->faculty ?? '-',
                $user?->study_program ?? '-',
                is_string($reg->status) ? $reg->status : $reg->status?->value,
                $reg->registered_at?->format('Y-m-d H:i:s') ?? '-',
                $ticket?->is_checked_in ? 'HADIR' : 'BELUM HADIR',
                $ticket?->checked_in_at?->format('Y-m-d H:i:s') ?? '-',
                $cert?->certificate_number ?? '-',
                $customStr,
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return $csvContent ?: '';
    }

    /**
     * Export recruitment applicants list to CSV string with UTF-8 BOM.
     */
    public function exportApplicantsCsv(Event $event): string
    {
        $applicants = Registration::with(['user', 'event'])
            ->where('event_id', $event->id)
            ->where('registration_type', 'COMMITTEE')
            ->orderBy('created_at', 'asc')
            ->get();

        $handle = fopen('php://temp', 'r+');
        if (! $handle) {
            return '';
        }

        // Write UTF-8 BOM
        fwrite($handle, "\xEF\xBB\xBF");

        // Header Row
        fputcsv($handle, [
            'No',
            'NIM',
            'Nama Lengkap',
            'Email Kampus',
            'Nomor HP / WhatsApp',
            'Fakultas',
            'Program Studi',
            'Pilihan Divisi Utama',
            'Pilihan Divisi Cadangan',
            'Status Seleksi',
            'Catatan Panitia',
            'Waktu Mendaftar',
            'Nama Berkas CV',
        ]);

        $no = 1;
        foreach ($applicants as $app) {
            $user = $app->user;

            fputcsv($handle, [
                $no++,
                $user?->nim ?? '-',
                $user?->name ?? '-',
                $user?->email ?? '-',
                $user?->phone_number ?? '-',
                $user?->faculty ?? '-',
                $user?->study_program ?? '-',
                $app->division_pref_1 ?? '-',
                $app->division_pref_2 ?? '-',
                is_string($app->selection_status) ? $app->selection_status : $app->selection_status?->value,
                $app->selection_notes ?? '-',
                $app->created_at?->format('Y-m-d H:i:s') ?? '-',
                $app->cv_file_path ? basename($app->cv_file_path) : '-',
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return $csvContent ?: '';
    }
}
