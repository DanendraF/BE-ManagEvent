<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\EventDetailResource;
use App\Http\Resources\EventListResource;
use App\Services\EventCatalogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventCatalogController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected EventCatalogService $eventCatalogService
    ) {}

    /**
     * Get list of all event categories.
     */
    public function categories(): JsonResponse
    {
        $categories = $this->eventCatalogService->getAllCategories();

        return $this->successResponse(
            data: CategoryResource::collection($categories),
            message: 'Daftar kategori kegiatan berhasil dimuat.',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Get paginated public published events catalog.
     */
    public function index(Request $request): JsonResponse
    {
        $events = $this->eventCatalogService->getPublicEvents(
            filters: $request->only(['search', 'category', 'type']),
            perPage: (int) $request->input('limit', $request->input('per_page', 15))
        );

        return $this->successResponse(
            data: EventListResource::collection($events),
            message: 'Katalog kegiatan berhasil dimuat.',
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
     * Get detail of a single published event by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $event = $this->eventCatalogService->getPublicEventBySlug($slug);

        return $this->successResponse(
            data: new EventDetailResource($event),
            message: 'Detail kegiatan berhasil dimuat.',
            statusCode: Response::HTTP_OK
        );
    }
}
