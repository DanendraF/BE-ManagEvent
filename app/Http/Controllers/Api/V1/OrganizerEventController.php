<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\CreateEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Http\Resources\EventDetailResource;
use App\Http\Resources\EventListResource;
use App\Models\Event;
use App\Services\OrganizerEventService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizerEventController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrganizerEventService $organizerEventService
    ) {}

    /**
     * Get list of events managed by authenticated organizer.
     */
    public function index(Request $request): JsonResponse
    {
        $events = $this->organizerEventService->getMyEvents(
            user: $request->user(),
            status: $request->query('status'),
            perPage: (int) $request->input('limit', 15)
        );

        return $this->successResponse(
            data: EventListResource::collection($events),
            message: 'Daftar kegiatan panitia berhasil dimuat.',
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
     * Create a new draft event.
     */
    public function store(CreateEventRequest $request): JsonResponse
    {
        $event = $this->organizerEventService->createDraft(
            user: $request->user(),
            data: $request->validated()
        );

        return $this->successResponse(
            data: new EventDetailResource($event),
            message: 'Draf kegiatan berhasil dibuat. Silakan lengkapi data sebelum diajukan ke DPM.',
            statusCode: Response::HTTP_CREATED
        );
    }

    /**
     * Get detail of an organizer event.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $event = Event::with(['category', 'organizer', 'divisions'])->findOrFail($id);
        $this->authorize('view', $event);

        return $this->successResponse(
            data: new EventDetailResource($event),
            message: 'Detail kegiatan panitia berhasil dimuat.',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Update an event (only if DRAFT or REJECTED).
     */
    public function update(UpdateEventRequest $request, string $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('update', $event);

        $updated = $this->organizerEventService->updateEvent(
            user: $request->user(),
            event: $event,
            data: $request->validated()
        );

        return $this->successResponse(
            data: new EventDetailResource($updated),
            message: 'Data kegiatan berhasil diperbarui.',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Submit draft event for Super Admin approval.
     */
    public function submit(Request $request, string $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('submit', $event);

        $submitted = $this->organizerEventService->submitForApproval(
            user: $request->user(),
            event: $event
        );

        return $this->successResponse(
            data: new EventDetailResource($submitted),
            message: 'Kegiatan berhasil diajukan ke DPM untuk diverifikasi dan disetujui.',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Delete a draft event.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('delete', $event);

        $this->organizerEventService->deleteDraft(
            user: $request->user(),
            event: $event
        );

        return $this->successResponse(
            data: null,
            message: 'Draf kegiatan berhasil dihapus.',
            statusCode: Response::HTTP_OK
        );
    }
}
