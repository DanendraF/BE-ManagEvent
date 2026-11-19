<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\RejectEventRequest;
use App\Http\Resources\Events\EventDetailResource;
use App\Http\Resources\Events\EventListResource;
use App\Models\Event;
use App\Services\Events\AdminEventService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminEventController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AdminEventService $adminEventService
    ) {}

    /**
     * Get all events for superadmin review.
     */
    public function index(Request $request): JsonResponse
    {
        $events = $this->adminEventService->getAllEventsForReview(
            status: $request->query('status'),
            perPage: (int) $request->input('limit', 15)
        );

        return $this->successResponse(
            data: EventListResource::collection($events),
            message: 'Daftar pengajuan kegiatan kampus berhasil dimuat.',
            statusCode: Response::HTTP_OK,
            meta: [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ]
        );
    }

    /**
     * Show detailed event for superadmin.
     */
    public function show(string $id): JsonResponse
    {
        $event = Event::with(['organizer', 'category', 'divisions'])->findOrFail($id);

        return $this->successResponse(
            data: new EventDetailResource($event),
            message: 'Detail pengajuan kegiatan berhasil dimuat.',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Approve event and publish to students.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('approve', $event);

        $approved = $this->adminEventService->approveEvent(
            admin: $request->user(),
            event: $event
        );

        return $this->successResponse(
            data: new EventDetailResource($approved),
            message: 'Kegiatan resmi disetujui (APPROVED) dan kini telah terpublikasi untuk mahasiswa.',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Reject event with official reason.
     */
    public function reject(RejectEventRequest $request, string $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('reject', $event);

        $rejected = $this->adminEventService->rejectEvent(
            admin: $request->user(),
            event: $event,
            reason: $request->validated('rejection_reason')
        );

        return $this->successResponse(
            data: new EventDetailResource($rejected),
            message: 'Pengajuan kegiatan telah ditolak. Catatan perbaikan telah dikirim ke panitia.',
            statusCode: Response::HTTP_OK
        );
    }
}
