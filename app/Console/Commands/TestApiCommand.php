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

        // Reset rate limiter for test IP and emails so automated runs are not throttled
        \Illuminate\Support\Facades\RateLimiter::clear('lemfti@uii.ac.id|127.0.0.1');
        \Illuminate\Support\Facades\RateLimiter::clear('admin.dpm@uii.ac.id|127.0.0.1');

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
     */
    protected function dispatchRequest(string $method, string $uri, array $parameters = [], array $server = []): Response
    {
        // Reset auth guards so multiple sequential requests in the CLI command do not reuse cached users
        app('auth')->forgetGuards();

        $server = array_merge([
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $server);

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

        return app()->handle($request);
    }
}
