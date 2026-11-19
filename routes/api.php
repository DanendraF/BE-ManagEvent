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
    Route::get('/certificates/verify/{hash}', [\App\Http\Controllers\Api\V1\CertificateController::class, 'verify']);

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
        Route::get('/events/{id}/registrations', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'eventRegistrations']);
        Route::get('/events/{id}/applicants', [\App\Http\Controllers\Api\V1\RecruitmentController::class, 'eventApplicants']);
        Route::patch('/registrations/{id}/selection', [\App\Http\Controllers\Api\V1\RecruitmentController::class, 'updateSelection']);
        Route::get('/events/{id}/attendance', [\App\Http\Controllers\Api\V1\AttendanceController::class, 'eventAttendance']);
        Route::get('/events/{id}/notifications', [\App\Http\Controllers\Api\V1\NotificationController::class, 'eventLogs']);
        Route::post('/events/{id}/reminders', [\App\Http\Controllers\Api\V1\NotificationController::class, 'broadcastReminders']);
    });

    // ==========================================
    // 3b. Presensi QR Hari-H (Scanner Panitia)
    // ==========================================
    Route::middleware(['auth:sanctum', 'role:ORGANIZER,SUPERADMIN'])->group(function () {
        Route::post('/attendance/check-in', [\App\Http\Controllers\Api\V1\AttendanceController::class, 'checkIn'])
            ->middleware('throttle:scanner');
    });

    // ==========================================
    // 4. Modul Pendaftaran Peserta & E-Tiket Mahasiswa
    // ==========================================
    Route::middleware(['auth:sanctum', 'role:STUDENT'])->group(function () {
        Route::post('/events/{id}/register', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'register'])
            ->middleware('throttle:registrations');
        Route::get('/my-tickets', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'myTickets']);
        Route::get('/my-tickets/{ticket_code}', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'showTicket']);
        Route::post('/my-tickets/{ticket_code}/cancel', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'cancelTicket']);

        // Jalur Pendaftaran Oprec Panitia Mahasiswa
        Route::post('/events/{id}/apply', [\App\Http\Controllers\Api\V1\RecruitmentController::class, 'apply'])
            ->middleware('throttle:registrations');
        Route::get('/my-applications', [\App\Http\Controllers\Api\V1\RecruitmentController::class, 'myApplications']);

        // E-Sertifikat Mahasiswa
        Route::get('/my-certificates', [\App\Http\Controllers\Api\V1\CertificateController::class, 'myCertificates']);
    });

    // Akses Dokumen CV Pendaftar (Mahasiswa pelamar, Panitia pemilik event, atau Superadmin)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/registrations/{id}/cv', [\App\Http\Controllers\Api\V1\RecruitmentController::class, 'downloadCv']);
    });

    // ==========================================
    // 5. Modul Super Admin Kurasi (DPM UII)
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
