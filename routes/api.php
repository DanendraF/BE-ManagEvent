<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Attendance\AttendanceController;
use App\Http\Controllers\Api\V1\Attendance\CertificateController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Events\AdminEventController;
use App\Http\Controllers\Api\V1\Events\EventCatalogController;
use App\Http\Controllers\Api\V1\Events\EventReportingController;
use App\Http\Controllers\Api\V1\Events\OrganizerEventController;
use App\Http\Controllers\Api\V1\Events\PosterUploadController;
use App\Http\Controllers\Api\V1\Notifications\NotificationController;
use App\Http\Controllers\Api\V1\Registrations\RecruitmentController;
use App\Http\Controllers\Api\V1\Registrations\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ==========================================
    // 1. Modul Autentikasi & RBAC (Auth)
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
    Route::get('/categories', [EventCatalogController::class, 'categories']);
    Route::get('/events', [EventCatalogController::class, 'index']);
    Route::get('/events/{slug}', [EventCatalogController::class, 'show']);
    Route::get('/certificates/verify/{hash}', [CertificateController::class, 'verify']);

    // ==========================================
    // 3. Modul Panitia Penyelenggara (Organizer Events)
    // ==========================================
    Route::middleware(['auth:sanctum', 'role:ORGANIZER,SUPERADMIN'])->prefix('organizer')->group(function () {
        Route::post('/upload-poster', [PosterUploadController::class, 'upload']);
        Route::get('/events', [OrganizerEventController::class, 'index']);
        Route::post('/events', [OrganizerEventController::class, 'store']);
        Route::get('/events/{id}', [OrganizerEventController::class, 'show']);
        Route::put('/events/{id}', [OrganizerEventController::class, 'update']);
        Route::post('/events/{id}/submit', [OrganizerEventController::class, 'submit']);
        Route::delete('/events/{id}', [OrganizerEventController::class, 'destroy']);
        Route::get('/events/{id}/registrations', [RegistrationController::class, 'eventRegistrations']);
        Route::get('/events/{id}/applicants', [RecruitmentController::class, 'eventApplicants']);
        Route::patch('/registrations/{id}/selection', [RecruitmentController::class, 'updateSelection']);
        Route::get('/events/{id}/attendance', [AttendanceController::class, 'eventAttendance']);
        Route::get('/events/{id}/notifications', [NotificationController::class, 'eventLogs']);
        Route::post('/events/{id}/reminders', [NotificationController::class, 'broadcastReminders']);

        // Rekapitulasi & Ekspor Laporan Kegiatan
        Route::get('/events/{id}/recap', [EventReportingController::class, 'recap']);
        Route::get('/events/{id}/export/participants', [EventReportingController::class, 'exportParticipants']);
        Route::get('/events/{id}/export/applicants', [EventReportingController::class, 'exportApplicants']);
        Route::post('/events/{id}/publish-instagram', [EventReportingController::class, 'publishInstagram']);
    });

    // ==========================================
    // 4. Presensi QR Hari-H (Attendance Scanner)
    // ==========================================
    Route::middleware(['auth:sanctum', 'role:ORGANIZER,SUPERADMIN'])->group(function () {
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
            ->middleware('throttle:scanner');
    });

    // ==========================================
    // 5. Modul Pendaftaran Peserta & E-Tiket Mahasiswa / Pengguna Terautentikasi
    // ==========================================
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/events/{id}/register', [RegistrationController::class, 'register'])
            ->middleware('throttle:registrations');
        Route::get('/my-tickets', [RegistrationController::class, 'myTickets']);
        Route::get('/my-tickets/{ticket_code}', [RegistrationController::class, 'showTicket']);
        Route::post('/my-tickets/{ticket_code}/cancel', [RegistrationController::class, 'cancelTicket'])
            ->middleware('throttle:registrations');

        // Jalur Pendaftaran Oprec Panitia Mahasiswa
        Route::post('/events/{id}/apply', [RecruitmentController::class, 'apply'])
            ->middleware('throttle:registrations');
        Route::get('/my-applications', [RecruitmentController::class, 'myApplications']);

        // E-Sertifikat Mahasiswa
        Route::get('/my-certificates', [CertificateController::class, 'myCertificates']);
    });

    // Akses Dokumen CV Pendaftar (Mahasiswa pelamar, Panitia pemilik event, atau Superadmin)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/registrations/{id}/cv', [RecruitmentController::class, 'downloadCv']);
    });

    // ==========================================
    // 6. Modul Super Admin Kurasi (DPM UII)
    // ==========================================
    Route::middleware(['auth:sanctum', 'role:SUPERADMIN'])->prefix('admin')->group(function () {
        Route::get('/events', [AdminEventController::class, 'index']);
        Route::get('/events/{id}', [AdminEventController::class, 'show']);
        Route::post('/events/{id}/approve', [AdminEventController::class, 'approve']);
        Route::post('/events/{id}/reject', [AdminEventController::class, 'reject']);
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
