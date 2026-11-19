<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Resources\AttendanceResource;
use App\Services\AttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Day-H QR Code scan & attendance check-in.
     */
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $result = $this->attendanceService->checkIn(
            scannerUser: $request->user(),
            qrPayload: $request->validated('qr_payload'),
            fallbackTicketCode: $request->validated('ticket_code')
        );

        return $this->successResponse(
            data: new AttendanceResource($result),
            message: 'Presensi kehadiran hari-H berhasil dikonfirmasi. E-Sertifikat otomatis diterbitkan untuk peserta.',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * Get event attendance list with summary metrics (Organizer only).
     */
    public function eventAttendance(Request $request, string $id): JsonResponse
    {
        $result = $this->attendanceService->getEventAttendance(
            user: $request->user(),
            eventId: $id,
            filters: $request->only(['is_checked_in', 'search']),
            perPage: (int) $request->input('limit', 15)
        );

        $paginator = $result['paginator'];

        return $this->successResponse(
            data: AttendanceResource::collection($paginator),
            message: 'Data rekapitulasi presensi kegiatan berhasil dimuat.',
            statusCode: Response::HTTP_OK,
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'statistics' => $result['stats'],
            ]
        );
    }
}
