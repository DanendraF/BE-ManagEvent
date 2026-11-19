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
    // 3. Modul Panitia Penyelenggara (Organizer)
    // ==========================================
    Route::middleware(['auth:sanctum', 'role:ORGANIZER,SUPERADMIN'])->prefix('organizer')->group(function () {
        Route::get('/events', [\App\Http\Controllers\Api\V1\OrganizerEventController::class, 'index']);
        Route::post('/events', [\App\Http\Controllers\Api\V1\OrganizerEventController::class, 'store']);
        Route::get('/events/{id}', [\App\Http\Controllers\Api\V1\OrganizerEventController::class, 'show']);
        Route::put('/events/{id}', [\App\Http\Controllers\Api\V1\OrganizerEventController::class, 'update']);
        Route::post('/events/{id}/submit', [\App\Http\Controllers\Api\V1\OrganizerEventController::class, 'submit']);
        Route::delete('/events/{id}', [\App\Http\Controllers\Api\V1\OrganizerEventController::class, 'destroy']);
    });

    // ==========================================
    // 4. Modul Super Admin Kurasi (DPM UII)
    // ==========================================
    Route::middleware(['auth:sanctum', 'role:SUPERADMIN'])->prefix('admin')->group(function () {
        Route::get('/events', [\App\Http\Controllers\Api\V1\AdminEventController::class, 'index']);
        Route::get('/events/{id}', [\App\Http\Controllers\Api\V1\AdminEventController::class, 'show']);
        Route::post('/events/{id}/approve', [\App\Http\Controllers\Api\V1\AdminEventController::class, 'approve']);
        Route::post('/events/{id}/reject', [\App\Http\Controllers\Api\V1\AdminEventController::class, 'reject']);
    });

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
