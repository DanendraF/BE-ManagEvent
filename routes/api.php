<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ==========================================
    // 1. Modul Autentikasi (Auth)
    // ==========================================
    Route::prefix('auth')->group(function () {
        // Public endpoints with brute force protection (5 req/min)
        Route::middleware('throttle:auth')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
        });

        // Protected endpoints (Requires valid Sanctum token)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // ==========================================
    // 2. Modul Katalog Kegiatan Publik (Events)
    // ==========================================
    Route::get('/categories', [\App\Http\Controllers\Api\V1\EventCatalogController::class, 'categories']);
    Route::get('/events', [\App\Http\Controllers\Api\V1\EventCatalogController::class, 'index']);
    Route::get('/events/{slug}', [\App\Http\Controllers\Api\V1\EventCatalogController::class, 'show']);

    // ==========================================
    // Role Verification Ping Endpoints (RBAC Test)
    // ==========================================
    Route::middleware(['auth:sanctum', 'role:ORGANIZER,SUPERADMIN'])->get('/test/organizer-only', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Akses terverifikasi untuk Panitia / Superadmin.',
        ]);
    });

    Route::middleware(['auth:sanctum', 'role:SUPERADMIN'])->get('/test/superadmin-only', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Akses terverifikasi untuk Superadmin.',
        ]);
    });
});
