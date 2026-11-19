<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Events\EventReportingService;
use App\Services\Events\InstagramPublishingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventReportingController extends Controller
{
    use ApiResponse;

    /**
     * Get comprehensive recap metrics for an event (Organizer/Superadmin).
     */
    public function recap(Request $request, string $id, EventReportingService $reportingService): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('view', $event);

        $recap = $reportingService->getEventRecap($event);

        return $this->successResponse(
            data: $recap,
            message: 'Rekapitulasi lengkap kegiatan kampus berhasil dimuat.'
        );
    }

    /**
     * Export participant registrations and attendance to CSV.
     */
    public function exportParticipants(Request $request, string $id, EventReportingService $reportingService): Response
    {
        $event = Event::findOrFail($id);
        $this->authorize('view', $event);

        $csv = $reportingService->exportParticipantsCsv($event);
        $filename = "rekap-peserta-{$event->slug}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Export committee recruitment applicants to CSV.
     */
    public function exportApplicants(Request $request, string $id, EventReportingService $reportingService): Response
    {
        $event = Event::findOrFail($id);
        $this->authorize('view', $event);

        $csv = $reportingService->exportApplicantsCsv($event);
        $filename = "rekap-pelamar-{$event->slug}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Trigger manual Instagram auto-post for an approved event.
     */
    public function publishInstagram(Request $request, string $id, InstagramPublishingService $instagramService): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('view', $event);

        $result = $instagramService->publishEvent($event);

        if ($result['status'] === 'FAILED') {
            return $this->errorResponse(
                errorCode: 'ERR_INSTAGRAM_PUBLISH',
                message: $result['error'] ?? 'Gagal mempublikasikan poster acara ke Instagram.',
                statusCode: 400
            );
        }

        $message = $result['status'] === 'ALREADY_POSTED'
            ? 'Kegiatan ini sudah pernah diposting ke Instagram sebelumnya (Idempotent).'
            : 'Poster kegiatan berhasil dipublikasikan secara otomatis ke Instagram Resmi Kampus.';

        return $this->successResponse(
            data: $result,
            message: $message
        );
    }
}
