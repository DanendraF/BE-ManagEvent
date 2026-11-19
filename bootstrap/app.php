<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // 1. Validation Exception
                if ($e instanceof ValidationException) {
                    $formattedErrors = [];
                    foreach ($e->errors() as $field => $messages) {
                        foreach ($messages as $msg) {
                            $formattedErrors[] = [
                                'field' => $field,
                                'message' => $msg,
                            ];
                        }
                    }

                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_VALIDATION',
                        'message' => 'Input form tidak valid atau tidak lengkap.',
                        'errors' => $formattedErrors,
                    ], 422);
                }

                // 1b. Unprocessable Entity (e.g. Magic Bytes Mismatch or Invalid Option)
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_VALIDATION',
                        'message' => $e->getMessage() ?: 'Input data atau berkas tidak valid.',
                        'errors' => [
                            [
                                'field' => 'file',
                                'message' => $e->getMessage(),
                            ],
                        ],
                    ], 422);
                }

                // 2. Authentication Exception
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_UNAUTHENTICATED',
                        'message' => 'Token tidak ditemukan atau telah kedaluwarsa. Silakan login kembali.',
                        'errors' => [],
                    ], 401);
                }

                // 3. Authorization / Forbidden
                if ($e instanceof AccessDeniedHttpException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_FORBIDDEN',
                        'message' => $e->getMessage() ?: 'Anda tidak memiliki hak akses untuk tindakan ini.',
                        'errors' => [],
                    ], 403);
                }

                // 4. Not Found
                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_NOT_FOUND',
                        'message' => $e->getMessage() ?: 'Resource data yang diminta tidak ditemukan.',
                        'errors' => [],
                    ], 404);
                }

                // 5. Unique Constraint Violation (Anti-Double Entry in PostgreSQL)
                if ($e instanceof UniqueConstraintViolationException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_ALREADY_REGISTERED',
                        'message' => 'Pendaftaran ditolak: Anda sudah terdaftar pada kegiatan ini.',
                        'errors' => [
                            [
                                'field' => 'registration',
                                'message' => 'Duplikasi terdeteksi pada level basis data.',
                            ],
                        ],
                    ], 409);
                }

                // 5b. Conflict HTTP Exception (e.g. Duplicate Registration application level)
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\ConflictHttpException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_ALREADY_REGISTERED',
                        'message' => $e->getMessage() ?: 'Konflik: Anda sudah terdaftar pada kegiatan ini.',
                        'errors' => [],
                    ], 409);
                }

                // 5c. Day-H QR Scanner: Already Checked In Guard
                if ($e instanceof \App\Exceptions\AlreadyCheckedInException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_ALREADY_CHECKED_IN',
                        'message' => $e->getMessage() ?: 'Tiket ini sudah pernah dipindai sebelumnya pada presensi hari-H.',
                        'errors' => [],
                    ], 409);
                }

                // 5d. Day-H QR Scanner: Invalid HMAC Cryptographic Signature
                if ($e instanceof \App\Exceptions\InvalidQrSignatureException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_INVALID_QR',
                        'message' => $e->getMessage() ?: 'Tanda tangan kriptografi QR tidak valid. Tiket palsu atau telah dimodifikasi.',
                        'errors' => [],
                    ], 400);
                }

                // 6. Rate Limiting / Throttle
                if ($e instanceof ThrottleRequestsException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_RATE_LIMITED',
                        'message' => 'Terlalu banyak permintaan. Silakan tunggu beberapa saat.',
                        'errors' => [],
                    ], 429);
                }

                // 7. General HTTP Exception
                if ($e instanceof HttpException) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'ERR_HTTP_' . $e->getStatusCode(),
                        'message' => $e->getMessage(),
                        'errors' => [],
                    ], $e->getStatusCode());
                }

                // 8. General Unhandled Exception
                $statusCode = 500;
                $message = config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan internal pada server.';

                return response()->json([
                    'status' => 'error',
                    'error_code' => 'ERR_INTERNAL_SERVER',
                    'message' => $message,
                    'errors' => config('app.debug') ? [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ] : [],
                ], $statusCode);
            }
        });
    })->create();
