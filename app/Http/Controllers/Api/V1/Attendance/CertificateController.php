<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Attendance\CertificateResource;
use App\Services\Attendance\AttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Public verification endpoint for e-certificates (Unauthenticated).
     */
    public function verify(string $hash): JsonResponse
    {
        $certificate = $this->attendanceService->verifyCertificate($hash);

        return $this->successResponse(
            data: new CertificateResource($certificate),
            message: 'Sertifikat resmi terverifikasi dan dinyatakan VALID oleh Universitas Islam Indonesia.',
            statusCode: Response::HTTP_OK
        );
    }

    /**
     * List earned certificates for authenticated student.
     */
    public function myCertificates(Request $request): JsonResponse
    {
        $paginator = $this->attendanceService->getMyCertificates(
            user: $request->user(),
            perPage: (int) $request->input('limit', 15)
        );

        return $this->successResponse(
            data: CertificateResource::collection($paginator),
            message: 'Daftar e-sertifikat kegiatan berhasil dimuat.',
            statusCode: Response::HTTP_OK,
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
        );
    }
}
