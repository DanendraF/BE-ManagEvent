<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * Send standard success response.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operasi berhasil dilakukan.',
        int $statusCode = Response::HTTP_OK,
        mixed $meta = null
    ): JsonResponse {
        $response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => $meta ?? [
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ],
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Send standard error response.
     */
    protected function errorResponse(
        string $message,
        string $errorCode,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        mixed $errors = []
    ): JsonResponse {
        $response = [
            'status' => 'error',
            'error_code' => $errorCode,
            'message' => $message,
            'errors' => $errors,
        ];

        return response()->json($response, $statusCode);
    }
}
