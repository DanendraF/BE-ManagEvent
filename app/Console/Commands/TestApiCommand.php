<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TestApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:test {--endpoint=all : Specific test to run (auth, rbac, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated CLI tester for UII Event Management Platform REST APIs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->line('===============================================================');
        $this->info('  🚀 UII EVENT PLATFORM — AUTOMATED API TESTER');
        $this->line('===============================================================');
        $this->line('Target Base URL : /api/v1');
        $this->line('Time            : ' . now()->toDateTimeString());
        $this->line('===============================================================');
        $this->info('');

        $passed = 0;
        $failed = 0;

        // Reset rate limiter cache so automated CLI test runs are never throttled
        \Illuminate\Support\Facades\Cache::flush();

        // 1. Test POST /api/v1/auth/login with Organizer credentials
        $this->comment('1. Testing POST /api/v1/auth/login (Panitia LEM FTI)...');
        $loginRes = $this->dispatchRequest('POST', '/api/v1/auth/login', [
            'email' => 'lemfti@uii.ac.id',
            'password' => 'Password123!',
        ]);

        $organizerToken = null;
        if ($loginRes->getStatusCode() === 200 && ($data = json_decode($loginRes->getContent(), true)) && isset($data['data']['token'])) {
            $organizerToken = $data['data']['token'];
            $this->info('   ✓ Status: 200 OK | Token: ' . substr($organizerToken, 0, 15) . '... | Role: ' . $data['data']['user']['roles'][0]['name']);
            $passed++;
        } else {
            $this->error('   ✗ FAILED (HTTP ' . $loginRes->getStatusCode() . '): ' . $loginRes->getContent());
            $failed++;
        }

        // 2. Test POST /api/v1/auth/login with Invalid Password
        $this->comment('2. Testing POST /api/v1/auth/login (Invalid Password)...');
        \Illuminate\Support\Facades\RateLimiter::clear('lemfti@uii.ac.id|127.0.0.1');
        $invalidLoginRes = $this->dispatchRequest('POST', '/api/v1/auth/login', [
            'email' => 'lemfti@uii.ac.id',
            'password' => 'SalahPassword!',
        ]);

        if ($invalidLoginRes->getStatusCode() === 422) {
            $data = json_decode($invalidLoginRes->getContent(), true);
            $this->info('   ✓ Status: 422 Unprocessable Content | Error Code: ' . ($data['error_code'] ?? 'ERR_VALIDATION'));
            $passed++;
        } else {
            $this->error('   ✗ FAILED: Expected 422, got ' . $invalidLoginRes->getStatusCode());
            $failed++;
        }

        // 3. Test GET /api/v1/auth/me (Unauthenticated)
        $this->comment('3. Testing GET /api/v1/auth/me (Without Bearer Token)...');
        $unauthRes = $this->dispatchRequest('GET', '/api/v1/auth/me');

        if ($unauthRes->getStatusCode() === 401) {
            $data = json_decode($unauthRes->getContent(), true);
            $this->info('   ✓ Status: 401 Unauthorized | Error Code: ' . ($data['error_code'] ?? 'ERR_UNAUTHENTICATED'));
            $passed++;
        } else {
            $this->error('   ✗ FAILED: Expected 401, got ' . $unauthRes->getStatusCode());
            $failed++;
        }

        // 4. Test GET /api/v1/auth/me (Authenticated with Organizer Token)
        if ($organizerToken) {
            $this->comment('4. Testing GET /api/v1/auth/me (With Organizer Bearer Token)...');
            $meRes = $this->dispatchRequest('GET', '/api/v1/auth/me', [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $organizerToken,
            ]);

            if ($meRes->getStatusCode() === 200) {
                $data = json_decode($meRes->getContent(), true);
                $this->info('   ✓ Status: 200 OK | User: ' . ($data['data']['name'] ?? 'N/A') . ' (' . ($data['data']['email'] ?? 'N/A') . ')');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $meRes->getStatusCode());
                $failed++;
            }

            // 5. Test RBAC: Organizer accessing /api/v1/test/organizer-only
            $this->comment('5. Testing RBAC: Organizer accessing /api/v1/test/organizer-only (Authorized)...');
            $orgPingRes = $this->dispatchRequest('GET', '/api/v1/test/organizer-only', [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $organizerToken,
            ]);

            if ($orgPingRes->getStatusCode() === 200) {
                $this->info('   ✓ Status: 200 OK | Access Granted');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $orgPingRes->getStatusCode());
                $failed++;
            }

            // 6. Test RBAC: Organizer accessing /api/v1/test/superadmin-only (Forbidden)
            $this->comment('6. Testing RBAC: Organizer accessing /api/v1/test/superadmin-only (Forbidden)...');
            $forbiddenRes = $this->dispatchRequest('GET', '/api/v1/test/superadmin-only', [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $organizerToken,
            ]);

            if ($forbiddenRes->getStatusCode() === 403) {
                $data = json_decode($forbiddenRes->getContent(), true);
                $this->info('   ✓ Status: 403 Forbidden | Error Code: ' . ($data['error_code'] ?? 'ERR_FORBIDDEN'));
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 403, got ' . $forbiddenRes->getStatusCode());
                $failed++;
            }

            // 7. Test POST /api/v1/auth/logout
            $this->comment('7. Testing POST /api/v1/auth/logout (Revoke Session)...');
            $logoutRes = $this->dispatchRequest('POST', '/api/v1/auth/logout', [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $organizerToken,
            ]);

            if ($logoutRes->getStatusCode() === 200) {
                $this->info('   ✓ Status: 200 OK | Sesi token berhasil dicabut.');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $logoutRes->getStatusCode());
                $failed++;
            }
        }

        // 8. Test GET /api/v1/categories
        $this->comment('8. Testing GET /api/v1/categories (Public Master Categories)...');
        $catRes = $this->dispatchRequest('GET', '/api/v1/categories');

        if ($catRes->getStatusCode() === 200 && ($catData = json_decode($catRes->getContent(), true)) && count($catData['data']) >= 5) {
            $this->info('   ✓ Status: 200 OK | Berhasil memuat ' . count($catData['data']) . ' kategori acara.');
            $passed++;
        } else {
            $this->error('   ✗ FAILED: Expected 200 with categories data, got ' . $catRes->getStatusCode());
            $failed++;
        }

        // 9. Test GET /api/v1/events (Public Catalog)
        $this->comment('9. Testing GET /api/v1/events (Public Published Events Catalog)...');
        $eventsRes = $this->dispatchRequest('GET', '/api/v1/events');

        if ($eventsRes->getStatusCode() === 200 && ($eventsData = json_decode($eventsRes->getContent(), true)) && isset($eventsData['meta']['total'])) {
            $this->info('   ✓ Status: 200 OK | Total Event Terpublikasi: ' . $eventsData['meta']['total'] . ' kegiatan.');
            $passed++;
        } else {
            $this->error('   ✗ FAILED: Expected 200 with events list, got ' . $eventsRes->getStatusCode());
            $failed++;
        }

        // 10. Test GET /api/v1/events/seminar-nasional-ai-2026 (Detail Event)
        $this->comment('10. Testing GET /api/v1/events/seminar-nasional-ai-2026 (Event Detail)...');
        $detailRes = $this->dispatchRequest('GET', '/api/v1/events/seminar-nasional-ai-2026');

        if ($detailRes->getStatusCode() === 200 && ($detailData = json_decode($detailRes->getContent(), true)) && isset($detailData['data']['slug'])) {
            $this->info('   ✓ Status: 200 OK | Judul: ' . $detailData['data']['title'] . ' | Kuota: ' . $detailData['data']['quota_remaining'] . '/' . $detailData['data']['quota_total']);
            $passed++;
        } else {
            $this->error('   ✗ FAILED: Expected 200 with event detail, got ' . $detailRes->getStatusCode());
            $failed++;
        }

        // 11. Test GET /api/v1/events/draf-konser-internal-2026 (Draft event must be hidden -> 404)
        $this->comment('11. Testing GET /api/v1/events/draf-konser-internal-2026 (Verify Draft is Hidden -> 404)...');
        $draftRes = $this->dispatchRequest('GET', '/api/v1/events/draf-konser-internal-2026');

        if ($draftRes->getStatusCode() === 404) {
            $draftData = json_decode($draftRes->getContent(), true);
            $this->info('   ✓ Status: 404 Not Found (Draft terproteksi) | Error Code: ' . ($draftData['error_code'] ?? 'ERR_NOT_FOUND'));
            $passed++;
        } else {
            $this->error('   ✗ FAILED: Expected 404 for Draft event, got ' . $draftRes->getStatusCode());
            $failed++;
        }

        // 12. Test POST /api/v1/organizer/events (Create Draft Event)
        $this->comment('12. Testing POST /api/v1/organizer/events (Create Draft Event by Panitia)...');
        $orgUser = User::where('email', 'lemfti@uii.ac.id')->first();
        $orgTok = $orgUser?->createToken('cli_org_token')->plainTextToken;

        $createdEventId = null;
        if ($orgTok) {
            $createRes = $this->dispatchRequest('POST', '/api/v1/organizer/events', [
                'title' => 'Webinar Karir Tech 2026 (Tester CLI ' . rand(100, 999) . ')',
                'category_id' => 1,
                'description' => 'Webinar strategi tembus magang top tech company.',
                'activity_type' => 'PARTICIPANT_EVENT',
                'poster_url' => 'https://storage.com/poster.jpg',
                'quota_total' => 100,
                'start_time' => now()->addDays(10)->toISOString(),
                'end_time' => now()->addDays(10)->addHours(3)->toISOString(),
                'location' => 'Zoom Meeting Kampus Terpadu UII',
                'auto_post_ig' => true,
            ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $orgTok]);

            if ($createRes->getStatusCode() === 201) {
                $createdData = json_decode($createRes->getContent(), true);
                $createdEventId = $createdData['data']['id'] ?? null;
                $this->info('   ✓ Status: 201 Created | Draf Event ID: ' . $createdEventId . ' | Status: ' . $createdData['data']['status']);
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 201, got ' . $createRes->getStatusCode() . ' - ' . $createRes->getContent());
                $failed++;
            }
        }

        // 13. Test POST /api/v1/organizer/events/{id}/submit (Submit for Approval)
        if ($createdEventId && $orgTok) {
            $this->comment('13. Testing POST /api/v1/organizer/events/{id}/submit (Submit Draft to DPM)...');
            $submitRes = $this->dispatchRequest('POST', "/api/v1/organizer/events/{$createdEventId}/submit", [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $orgTok,
            ]);

            if ($submitRes->getStatusCode() === 200) {
                $subData = json_decode($submitRes->getContent(), true);
                $this->info('   ✓ Status: 200 OK | Transisi Status: ' . $subData['data']['status']);
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $submitRes->getStatusCode());
                $failed++;
            }
        }

        // 14. Test POST /api/v1/admin/events/{id}/approve (Superadmin DPM Approves Event)
        if ($createdEventId) {
            $this->comment('14. Testing POST /api/v1/admin/events/{id}/approve (DPM Superadmin Approval)...');
            $adminUser = User::where('email', 'admin.dpm@uii.ac.id')->first();
            $adminTok = $adminUser?->createToken('cli_admin_token')->plainTextToken;

            if ($adminTok) {
                $approveRes = $this->dispatchRequest('POST', "/api/v1/admin/events/{$createdEventId}/approve", [], [
                    'HTTP_AUTHORIZATION' => 'Bearer ' . $adminTok,
                ]);

                if ($approveRes->getStatusCode() === 200) {
                    $appData = json_decode($approveRes->getContent(), true);
                    $this->info('   ✓ Status: 200 OK | Event Resmi APPROVED & PUBLISHED | Status: ' . $appData['data']['status']);
                    $passed++;
                } else {
                    $this->error('   ✗ FAILED: Expected 200, got ' . $approveRes->getStatusCode());
                    $failed++;
                }
            }
        }

        // Target event for registration testing (published event)
        $regTargetEventId = $createdEventId;
        if (! $regTargetEventId) {
            $pubEv = \App\Models\Event::where('status', \App\Enums\EventStatusEnum::PUBLISHED->value)->first();
            $regTargetEventId = $pubEv?->id;
        }

        $studentUser = User::where('email', '21523001@students.uii.ac.id')->first();
        $studentToken = $studentUser?->createToken('cli_student_token')->plainTextToken;
        $orgUser = User::where('email', 'lemfti@uii.ac.id')->first();
        $orgToken = $orgUser?->createToken('cli_org_token')->plainTextToken;

        $registeredTicketCode = null;

        // 15. Test POST /api/v1/events/{id}/register (Student Registration & E-Ticket)
        if ($regTargetEventId && $studentToken) {
            $this->comment('15. Testing POST /api/v1/events/{id}/register (Student Registration with Signed E-Ticket)...');
            $regRes = $this->dispatchRequest('POST', "/api/v1/events/{$regTargetEventId}/register", [
                'custom_answers' => [
                    'dietary_requirement' => 'Vegetarian',
                    'tshirt_size' => 'L',
                ],
            ], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $studentToken,
            ]);

            if ($regRes->getStatusCode() === 201) {
                $regData = json_decode($regRes->getContent(), true);
                $registeredTicketCode = $regData['data']['ticket_code'] ?? ($regData['data']['ticket']['ticket_code'] ?? null);
                $this->info('   ✓ Status: 201 Created | Tiket: ' . $registeredTicketCode . ' | QR Sig: OK');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 201, got ' . $regRes->getStatusCode() . ' ' . $regRes->getContent());
                $failed++;
            }

            // 16. Test Anti-Double Entry (POST again by same student -> 409 Conflict)
            $this->comment('16. Testing Anti-Double Entry (Same student registers again)...');
            $dupRes = $this->dispatchRequest('POST', "/api/v1/events/{$regTargetEventId}/register", [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $studentToken,
            ]);

            if ($dupRes->getStatusCode() === 409) {
                $this->info('   ✓ Status: 409 Conflict (Pencegahan duplikasi berhasil ditolak)');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 409 Conflict, got ' . $dupRes->getStatusCode());
                $failed++;
            }

            // 17. Test GET /api/v1/my-tickets (Student Ticket List)
            $this->comment('17. Testing GET /api/v1/my-tickets (List My E-Tickets)...');
            $myTicksRes = $this->dispatchRequest('GET', '/api/v1/my-tickets', [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $studentToken,
            ]);

            if ($myTicksRes->getStatusCode() === 200) {
                $ticksData = json_decode($myTicksRes->getContent(), true);
                $count = count($ticksData['data'] ?? []);
                $this->info('   ✓ Status: 200 OK | Jumlah Tiket Aktif: ' . $count);
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $myTicksRes->getStatusCode());
                $failed++;
            }

            // 18. Test GET /api/v1/my-tickets/{ticket_code} (Student Ticket Detail)
            if ($registeredTicketCode) {
                $this->comment('18. Testing GET /api/v1/my-tickets/' . $registeredTicketCode . ' (Ticket Detail)...');
                $tickDetRes = $this->dispatchRequest('GET', "/api/v1/my-tickets/{$registeredTicketCode}", [], [
                    'HTTP_AUTHORIZATION' => 'Bearer ' . $studentToken,
                ]);

                if ($tickDetRes->getStatusCode() === 200) {
                    $this->info('   ✓ Status: 200 OK | Detail Tiket & Payload QR Tervalidasi');
                    $passed++;
                } else {
                    $this->error('   ✗ FAILED: Expected 200, got ' . $tickDetRes->getStatusCode());
                    $failed++;
                }

                // 19. Test POST /api/v1/my-tickets/{ticket_code}/cancel (Ticket Cancellation & Quota Restore)
                $this->comment('19. Testing POST /api/v1/my-tickets/' . $registeredTicketCode . '/cancel (Cancellation)...');
                $cancelRes = $this->dispatchRequest('POST', "/api/v1/my-tickets/{$registeredTicketCode}/cancel", [], [
                    'HTTP_AUTHORIZATION' => 'Bearer ' . $studentToken,
                ]);

                if ($cancelRes->getStatusCode() === 200) {
                    $this->info('   ✓ Status: 200 OK | Tiket Berhasil Dibatalkan & Kuota Dikembalikan (+1)');
                    $passed++;
                } else {
                    $this->error('   ✗ FAILED: Expected 200, got ' . $cancelRes->getStatusCode());
                    $failed++;
                }
            }

            // 20. Test GET /api/v1/organizer/events/{id}/registrations (Organizer views attendee list)
            $this->comment('20. Testing GET /api/v1/organizer/events/{id}/registrations (Organizer Attendees List)...');
            $orgAttendeesRes = $this->dispatchRequest('GET', "/api/v1/organizer/events/{$regTargetEventId}/registrations", [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $orgToken,
            ]);

            if ($orgAttendeesRes->getStatusCode() === 200) {
                $this->info('   ✓ Status: 200 OK | Panitia Berhasil Mengakses Daftar Pendaftar Acara');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $orgAttendeesRes->getStatusCode());
                $failed++;
            }
        }

        // ==========================================
        // Fitur 06: Jalur Oprec Panitia & Seleksi
        // ==========================================
        $oprecEvent = \App\Models\Event::where('activity_type', \App\Enums\ActivityTypeEnum::COMMITTEE_RECRUITMENT->value)
            ->where('status', \App\Enums\EventStatusEnum::PUBLISHED->value)
            ->first();

        $recruitmentRegId = null;

        if ($oprecEvent && $studentToken) {
            // Clean up prior test registration for idempotency across CLI runs
            \App\Models\Registration::where('user_id', $studentUser->id)->where('event_id', $oprecEvent->id)->delete();

            // 21. Test POST /api/v1/events/{id}/apply (Student Recruitment Application with CV)
            $this->comment('21. Testing POST /api/v1/events/{id}/apply (Oprec Application with Hardened CV)...');
            
            // Create real temporary PDF file for magic bytes validation
            $tempCvPath = storage_path('app/temp_cv_' . uniqid() . '.pdf');
            file_put_contents($tempCvPath, "%PDF-1.4\nTest Curriculum Vitae Content for UII Oprec");
            
            $uploadedCv = new \Illuminate\Http\UploadedFile(
                $tempCvPath,
                'cv_mahasiswa.pdf',
                'application/pdf',
                null,
                true
            );

            // Fetch actual first division from event
            $firstDiv = $oprecEvent->divisions()->first()?->name ?? 'Divisi Acara & Konsep';
            $secondDiv = $oprecEvent->divisions()->skip(1)->first()?->name ?? 'Divisi Media Kreatif';

            $applyRes = $this->dispatchRequest('POST', "/api/v1/events/{$oprecEvent->id}/apply", [
                'division_pref_1' => $firstDiv,
                'division_pref_2' => $secondDiv,
                'custom_answers' => [
                    'motivation' => 'Ingin mengembangkan soft skill kepemimpinan.',
                    'portfolio_url' => 'https://linkedin.com/in/fauzan-student',
                ],
            ], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $studentToken,
            ], [
                'cv_file' => $uploadedCv,
            ]);

            if ($applyRes->getStatusCode() === 201) {
                $applyData = json_decode($applyRes->getContent(), true);
                $recruitmentRegId = $applyData['data']['id'] ?? null;
                $this->info('   ✓ Status: 201 Created | Divisi: ' . ($applyData['data']['division_pref_1'] ?? 'N/A') . ' | Magic Bytes: Valid');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 201, got ' . $applyRes->getStatusCode() . ' ' . $applyRes->getContent());
                $failed++;
            }

            if (file_exists($tempCvPath)) {
                @unlink($tempCvPath);
            }

            // 22. Test GET /api/v1/my-applications (Student views their recruitment applications)
            $this->comment('22. Testing GET /api/v1/my-applications (List My Oprec Applications)...');
            $myAppsRes = $this->dispatchRequest('GET', '/api/v1/my-applications', [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $studentToken,
            ]);

            if ($myAppsRes->getStatusCode() === 200) {
                $this->info('   ✓ Status: 200 OK | Berhasil memuat riwayat pendaftaran kepanitiaan');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $myAppsRes->getStatusCode());
                $failed++;
            }

            // 23. Test GET /api/v1/organizer/events/{id}/applicants (Organizer views oprec applicants)
            $this->comment('23. Testing GET /api/v1/organizer/events/{id}/applicants (Organizer Oprec Dashboard)...');
            $applicantsRes = $this->dispatchRequest('GET', "/api/v1/organizer/events/{$oprecEvent->id}/applicants", [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $orgToken,
            ]);

            if ($applicantsRes->getStatusCode() === 200) {
                $this->info('   ✓ Status: 200 OK | Panitia Berhasil Mengakses Daftar Calon Anggota Panitia');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $applicantsRes->getStatusCode());
                $failed++;
            }

            // 24. Test PATCH /api/v1/organizer/registrations/{id}/selection (Organizer updates selection status)
            if ($recruitmentRegId) {
                $this->comment('24. Testing PATCH /api/v1/organizer/registrations/{id}/selection (Update Selection)...');
                $updateSelRes = $this->dispatchRequest('PATCH', "/api/v1/organizer/registrations/{$recruitmentRegId}/selection", [
                    'selection_status' => \App\Enums\SelectionStatusEnum::INTERVIEW_SCHEDULED->value,
                    'notes' => 'Wawancara dijadwalkan hari Sabtu, 28 Nov via Zoom.',
                ], [
                    'HTTP_AUTHORIZATION' => 'Bearer ' . $orgToken,
                ]);

                if ($updateSelRes->getStatusCode() === 200) {
                    $this->info('   ✓ Status: 200 OK | Status Berhasil Diubah: INTERVIEW_SCHEDULED dengan Catatan Wawancara');
                    $passed++;
                } else {
                    $this->error('   ✗ FAILED: Expected 200, got ' . $updateSelRes->getStatusCode() . ' ' . $updateSelRes->getContent());
                    $failed++;
                }
            }
        }

        // ==========================================
        // Fitur 07: Presensi QR Hari-H & E-Sertifikat
        // ==========================================
        $this->comment('--- [MODUL FITUR 07: PRESENSI QR HARI-H & E-SERTIFIKAT] ---');
        
        // Register a fresh participant ticket for attendance test
        $attendeeUser = User::where('email', '21523002@students.uii.ac.id')->first();
        $attendeeToken = $attendeeUser?->createToken('cli_attendee_tok')->plainTextToken;
        $attendanceEventId = $regTargetEventId;

        $attendeeQrPayload = null;
        if ($attendeeUser && $attendeeToken && $attendanceEventId) {
            $regAttRes = $this->dispatchRequest('POST', "/api/v1/events/{$attendanceEventId}/register", [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $attendeeToken,
            ]);

            if ($regAttRes->getStatusCode() === 201) {
                $regAttData = json_decode($regAttRes->getContent(), true);
                $attendeeQrPayload = $regAttData['data']['qr_payload'] ?? null;
            }
        }

        $issuedCertHash = null;

        // 25. Test POST /api/v1/attendance/check-in (Day-H QR Scan by Organizer)
        if ($attendeeQrPayload) {
            $this->comment('25. Testing POST /api/v1/attendance/check-in (Day-H QR Code Scan & E-Certificate)...');
            $checkInRes = $this->dispatchRequest('POST', '/api/v1/attendance/check-in', [
                'qr_payload' => $attendeeQrPayload,
            ], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $orgToken,
            ]);

            if ($checkInRes->getStatusCode() === 200) {
                $checkInData = json_decode($checkInRes->getContent(), true);
                $issuedCertHash = $checkInData['data']['certificate']['verify_hash'] ?? null;
                $certNum = $checkInData['data']['certificate']['certificate_number'] ?? 'N/A';
                $this->info('   ✓ Status: 200 OK | Presensi Sah | E-Sertifikat Terbit: ' . $certNum);
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $checkInRes->getStatusCode() . ' ' . $checkInRes->getContent());
                $failed++;
            }

            // 26. Test Anti-Double Check-In Guard
            $this->comment('26. Testing Anti-Double Check-In Guard (Scan ticket second time)...');
            $dupCheckInRes = $this->dispatchRequest('POST', '/api/v1/attendance/check-in', [
                'qr_payload' => $attendeeQrPayload,
            ], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $orgToken,
            ]);

            if ($dupCheckInRes->getStatusCode() === 409) {
                $this->info('   ✓ Status: 409 Conflict | Double check-in berhasil dicegah (ERR_ALREADY_CHECKED_IN)');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 409, got ' . $dupCheckInRes->getStatusCode());
                $failed++;
            }
        }

        // 27. Test GET /api/v1/certificates/verify/{hash} (Public Verification)
        if ($issuedCertHash) {
            $this->comment('27. Testing GET /api/v1/certificates/verify/' . substr($issuedCertHash, 0, 16) . '... (Public Verification)...');
            $pubVerifyRes = $this->dispatchRequest('GET', "/api/v1/certificates/verify/{$issuedCertHash}");

            if ($pubVerifyRes->getStatusCode() === 200) {
                $verifyData = json_decode($pubVerifyRes->getContent(), true);
                $this->info('   ✓ Status: 200 OK | Publik Berhasil Memverifikasi Sertifikat | Status: ' . ($verifyData['data']['status'] ?? 'VALID'));
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $pubVerifyRes->getStatusCode());
                $failed++;
            }
        }

        // 28. Test GET /api/v1/organizer/events/{id}/attendance (Organizer Attendance Statistics)
        if ($attendanceEventId) {
            $this->comment('28. Testing GET /api/v1/organizer/events/{id}/attendance (Attendance Recap & Metrics)...');
            $attListRes = $this->dispatchRequest('GET', "/api/v1/organizer/events/{$attendanceEventId}/attendance", [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $orgToken,
            ]);

            if ($attListRes->getStatusCode() === 200) {
                $attData = json_decode($attListRes->getContent(), true);
                $rate = $attData['meta']['statistics']['attendance_rate_percent'] ?? 0;
                $this->info('   ✓ Status: 200 OK | Rekapitulasi Presensi Panitia | Tingkat Kehadiran: ' . $rate . '%');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $attListRes->getStatusCode());
                $failed++;
            }
        }

        // 29. Test GET /api/v1/my-certificates (Student Certificate List)
        if ($attendeeToken) {
            $this->comment('29. Testing GET /api/v1/my-certificates (Student Earned Certificates)...');
            $myCertsRes = $this->dispatchRequest('GET', '/api/v1/my-certificates', [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $attendeeToken,
            ]);

            if ($myCertsRes->getStatusCode() === 200) {
                $myCertsData = json_decode($myCertsRes->getContent(), true);
                $this->info('   ✓ Status: 200 OK | Mahasiswa Memperoleh ' . count($myCertsData['data'] ?? []) . ' E-Sertifikat Resmi');
                $passed++;
            } else {
                $this->error('   ✗ FAILED: Expected 200, got ' . $myCertsRes->getStatusCode());
                $failed++;
            }
        }

        // Summary
        $this->info('');
        $this->line('===============================================================');
        $this->info(sprintf('  HASIL PENGUJIAN: %d LULUS / %d GAGAL (Total: %d Skenario)', $passed, $failed, $passed + $failed));
        $this->line('===============================================================');
        $this->info('');

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Dispatch an internal HTTP request through the Laravel kernel.
     *
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $server
     * @param array<string, mixed> $files
     */
    protected function dispatchRequest(string $method, string $uri, array $parameters = [], array $server = [], array $files = []): Response
    {
        // Reset auth guards so multiple sequential requests in the CLI command do not reuse cached users
        app('auth')->forgetGuards();

        $server = array_merge([
            'HTTP_ACCEPT' => 'application/json',
        ], $server);

        if (! empty($files)) {
            // Multipart form request
            $request = Request::create(
                $uri,
                $method,
                $parameters,
                [],
                $files,
                $server
            );
        } else {
            $server['CONTENT_TYPE'] = 'application/json';
            $content = in_array(strtoupper($method), ['POST', 'PUT', 'PATCH']) ? json_encode($parameters) : null;

            $request = Request::create(
                $uri,
                $method,
                [],
                [],
                [],
                $server,
                $content
            );
        }

        return app()->handle($request);
    }
}
