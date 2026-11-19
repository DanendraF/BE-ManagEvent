<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Registrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrations\RegisterParticipantRequest;
use App\Http\Resources\Registrations\RegistrationResource;
use App\Http\Resources\Registrations\TicketResource;
use App\Services\Registrations\RegistrationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected RegistrationService $registrationService
    ) {}

    /**
     * Register student as event participant.
     */
    public function register(RegisterParticipantRequest $request, string $id): JsonResponse
    {
        $ticket = $this->registrationService->registerParticipant(
            user: $request->user(),
            eventId: $id,
            customAnswers: $request->validated('custom_answers', [])
        );

        return $this->successResponse(
            data: new TicketResource($ticket),
            message: 'Pendaftaran kegiatan berhasil dikonfirmasi. E-Tiket ber-QR Code telah diterbitkan.',
            statusCode: Response::HTTP_CREATED
        );
    }

    /**
     * Get list of active tickets for authenticated student.
     */
    public function myTickets(Request $request): JsonResponse
    {
        $tickets = $this->registrationService->getMyTickets(
            user: $request->user(),
            perPage: (int) $request->input('limit', 15)
        );

        return $this->successResponse(
            data: TicketResource::collection($tickets),
            message: 'Daftar e-tiket berhasil dimuat.',
            statusCode: Response::HTTP_OK,
            meta: [
                'current_page' => $tickets->currentPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'last_page' => $tickets->lastPage(),
            ]
        );
    }

    /**
     * Get single ticket by ticket code.
     */
    public function showTicket(Request $request, string $ticket_code): JsonResponse
    {
        $ticket = $this->registrationService->getTicketByCode(
            user: $request->user(),
            ticketCode: $ticket_code
        );

        return $this->successResponse(
            data: new TicketResource($ticket),
            message: 'Detail e-tiket berhasil dimuat.',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Cancel an active ticket registration.
     */
    public function cancelTicket(Request $request, string $ticket_code): JsonResponse
    {
        $ticket = $this->registrationService->cancelRegistration(
            user: $request->user(),
            ticketCode: $ticket_code
        );

        return $this->successResponse(
            data: new TicketResource($ticket),
            message: 'Pendaftaran kegiatan berhasil dibatalkan. Kuota telah dikembalikan.',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Organizer endpoint: Get attendee list for managed event.
     */
    public function eventRegistrations(Request $request, string $id): JsonResponse
    {
        $registrations = $this->registrationService->getEventRegistrations(
            user: $request->user(),
            eventId: $id,
            status: $request->query('status'),
            perPage: (int) $request->input('limit', 15)
        );

        return $this->successResponse(
            data: RegistrationResource::collection($registrations),
            message: 'Rekap data pendaftar kegiatan berhasil dimuat.',
            statusCode: Response::HTTP_OK,
            meta: [
                'current_page' => $registrations->currentPage(),
                'per_page' => $registrations->perPage(),
                'total' => $registrations->total(),
                'last_page' => $registrations->lastPage(),
            ]
        );
    }
}
