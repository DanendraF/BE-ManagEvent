<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Registrations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitments\ApplyRecruitmentRequest;
use App\Http\Requests\Recruitments\UpdateSelectionRequest;
use App\Http\Resources\Registrations\RegistrationResource;
use App\Services\Registrations\RecruitmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class RecruitmentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected RecruitmentService $recruitmentService
    ) {}

    /**
     * Submit application for committee open recruitment.
     */
    public function apply(ApplyRecruitmentRequest $request, string $id): JsonResponse
    {
        /** @var \Illuminate\Http\UploadedFile $cvFile */
        $cvFile = $request->file('cv_file');

        $registration = $this->recruitmentService->applyForRecruitment(
            user: $request->user(),
            eventId: $id,
            data: $request->validated(),
            cvFile: $cvFile
        );

        return $this->successResponse(
            data: new RegistrationResource($registration),
            message: 'Pendaftaran open recruitment panitia berhasil dikirim.',
            statusCode: Response::HTTP_CREATED
        );
    }

    /**
     * Get list of recruitment applications for authenticated student.
     */
    public function myApplications(Request $request): JsonResponse
    {
        $paginator = $this->recruitmentService->getMyApplications(
            user: $request->user(),
            perPage: (int) $request->input('limit', 15)
        );

        return $this->successResponse(
            data: RegistrationResource::collection($paginator),
            message: 'Daftar riwayat pendaftaran open recruitment berhasil dimuat.',
            statusCode: Response::HTTP_OK,
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
        );
    }

    /**
     * Get list of applicants for an event (Organizer only).
     */
    public function eventApplicants(Request $request, string $id): JsonResponse
    {
        $paginator = $this->recruitmentService->getEventApplicants(
            user: $request->user(),
            eventId: $id,
            filters: $request->only(['division', 'status', 'search']),
            perPage: (int) $request->input('limit', 15)
        );

        return $this->successResponse(
            data: RegistrationResource::collection($paginator),
            message: 'Daftar calon panitia (pendaftar oprec) berhasil dimuat.',
            statusCode: Response::HTTP_OK,
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
        );
    }

    /**
     * Update recruitment applicant selection status (Organizer only).
     */
    public function updateSelection(UpdateSelectionRequest $request, string $id): JsonResponse
    {
        $statusEnum = \App\Enums\SelectionStatusEnum::from($request->validated('selection_status'));

        $registration = $this->recruitmentService->updateSelectionStatus(
            user: $request->user(),
            registrationId: $id,
            status: $statusEnum,
            notes: $request->validated('notes')
        );

        return $this->successResponse(
            data: new RegistrationResource($registration),
            message: "Status seleksi pendaftar berhasil diperbarui menjadi {$statusEnum->value}."
        );
    }

    /**
     * Download or view applicant CV document securely.
     */
    public function downloadCv(Request $request, string $id): Response
    {
        return $this->recruitmentService->streamApplicantCv(
            user: $request->user(),
            registrationId: $id
        );
    }
}
