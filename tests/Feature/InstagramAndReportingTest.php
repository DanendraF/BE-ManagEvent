<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityTypeEnum;
use App\Enums\EventStatusEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\RegistrationStatusEnum;
use App\Jobs\PostToInstagramJob;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\NotificationLog;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Services\InstagramPublishingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstagramAndReportingTest extends TestCase
{
    use RefreshDatabase;

    protected User $organizer;
    protected User $otherOrganizer;
    protected User $superAdmin;
    protected User $student;
    protected Event $event;
    protected EventCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $organizerRole = Role::where('name', 'ORGANIZER')->firstOrFail();
        $superAdminRole = Role::where('name', 'SUPERADMIN')->firstOrFail();
        $studentRole = Role::where('name', 'STUDENT')->firstOrFail();

        $this->organizer = User::factory()->create([
            'email' => 'org.report@uii.ac.id',
            'phone_number' => '081234567811',
        ]);
        $this->organizer->roles()->attach($organizerRole->id);

        $this->otherOrganizer = User::factory()->create([
            'email' => 'other.org.report@uii.ac.id',
            'phone_number' => '081234567812',
        ]);
        $this->otherOrganizer->roles()->attach($organizerRole->id);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin.dpm.report@uii.ac.id',
            'phone_number' => '081234567819',
        ]);
        $this->superAdmin->roles()->attach($superAdminRole->id);

        $this->student = User::factory()->create([
            'email' => 'student.report@uii.ac.id',
            'phone_number' => '081234567813',
            'nim' => '21523999',
            'faculty' => 'Fakultas Teknologi Industri',
            'study_program' => 'Informatika',
        ]);
        $this->student->roles()->attach($studentRole->id);

        $this->category = EventCategory::first();

        $this->event = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
            'title' => 'Festival Teknologi & Karir BSI UII 2026',
            'slug' => 'festival-teknologi-karir-bsi-uii-2026',
            'description' => 'Pameran inovasi dan seminar karir teknologi.',
            'poster_url' => 'https://storage.uii.ac.id/posters/festival.jpg',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'start_time' => now()->addDays(7),
            'end_time' => now()->addDays(7)->addHours(8),
            'location' => 'Gedung Kuliah Umum (GKU) Prof. Dr. Sardjito UII',
            'quota_total' => 150,
            'quota_remaining' => 149,
            'status' => EventStatusEnum::PUBLISHED,
        ]);
    }

    public function test_it_dispatches_post_to_instagram_job_when_event_is_approved(): void
    {
        Queue::fake();

        $pendingEvent = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
            'title' => 'Seminar Technopreneur UII 2026',
            'slug' => 'seminar-technopreneur-uii-2026',
            'description' => 'Seminar kewirausahaan digital.',
            'poster_url' => 'https://storage.uii.ac.id/posters/techno.jpg',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'start_time' => now()->addDays(10),
            'end_time' => now()->addDays(10)->addHours(3),
            'location' => 'Auditorium FTI UII',
            'quota_total' => 200,
            'quota_remaining' => 200,
            'status' => EventStatusEnum::PENDING_APPROVAL,
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v1/admin/events/{$pendingEvent->id}/approve");

        $response->assertStatus(200);

        Queue::assertPushed(PostToInstagramJob::class, function (PostToInstagramJob $job) use ($pendingEvent) {
            return $job->eventId === $pendingEvent->id;
        });
    }

    public function test_it_publishes_event_to_instagram_via_meta_graph_api(): void
    {
        Config::set('services.instagram.account_id', '17841400000000001');
        Config::set('services.instagram.access_token', 'EAABdummy_meta_access_token');
        Config::set('services.instagram.graph_version', 'v19.0');
        Config::set('services.instagram.base_url', 'https://graph.facebook.com');

        Http::fake([
            'https://graph.facebook.com/v19.0/17841400000000001/media' => Http::response(['id' => 'container_998877'], 200),
            'https://graph.facebook.com/v19.0/17841400000000001/media_publish' => Http::response(['id' => 'post_11223344'], 200),
        ]);

        $service = app(InstagramPublishingService::class);
        $result = $service->publishEvent($this->event);

        $this->assertSame('SENT', $result['status']);
        $this->assertSame('post_11223344', $result['post_id']);

        $this->assertDatabaseHas('notification_logs', [
            'event_id' => $this->event->id,
            'channel' => NotificationChannelEnum::INSTAGRAM->value,
            'delivery_status' => 'SENT',
        ]);
    }

    public function test_it_prevents_duplicate_instagram_posts_via_idempotency_guard(): void
    {
        Config::set('services.instagram.account_id', '17841400000000001');
        Config::set('services.instagram.access_token', 'EAABdummy_meta_access_token');

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['id' => 'post_unique_123'], 200),
        ]);

        $service = app(InstagramPublishingService::class);

        // First attempt posts to IG
        $first = $service->publishEvent($this->event);
        $this->assertSame('SENT', $first['status']);

        // Second attempt must return ALREADY_POSTED without calling external API again
        $second = $service->publishEvent($this->event);
        $this->assertSame('ALREADY_POSTED', $second['status']);

        $this->assertDatabaseCount('notification_logs', 1);
    }

    public function test_it_handles_instagram_api_failure_gracefully(): void
    {
        Config::set('services.instagram.account_id', '17841400000000001');
        Config::set('services.instagram.access_token', 'EAABdummy_meta_access_token');

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['error' => ['message' => 'OAuthException: Invalid token']], 401),
        ]);

        $service = app(InstagramPublishingService::class);
        $result = $service->publishEvent($this->event);

        $this->assertSame('FAILED', $result['status']);

        $this->assertDatabaseHas('notification_logs', [
            'event_id' => $this->event->id,
            'channel' => NotificationChannelEnum::INSTAGRAM->value,
            'delivery_status' => 'FAILED',
        ]);
    }

    public function test_organizer_can_fetch_comprehensive_event_recap(): void
    {
        // Add sample registration
        $reg = Registration::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->student->id,
            'event_id' => $this->event->id,
            'registration_type' => 'PARTICIPANT',
            'status' => RegistrationStatusEnum::REGISTERED,
            'registered_at' => now(),
        ]);

        Ticket::create([
            'id' => (string) Str::uuid(),
            'registration_id' => $reg->id,
            'ticket_code' => 'TKT-2026-RECAPTEST',
            'qr_payload' => 'dummy',
            'is_checked_in' => true,
        ]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->getJson("/api/v1/organizer/events/{$this->event->id}/recap");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.participants.total_registered', 1)
            ->assertJsonPath('data.participants.attended_count', 1)
            ->assertJsonPath('data.participants.attendance_rate_percent', 100)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'event',
                    'participants',
                    'certificates',
                    'notifications',
                ],
            ]);
    }

    public function test_organizer_can_export_participants_csv(): void
    {
        $reg = Registration::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->student->id,
            'event_id' => $this->event->id,
            'registration_type' => 'PARTICIPANT',
            'status' => RegistrationStatusEnum::REGISTERED,
            'registered_at' => now(),
        ]);

        Ticket::create([
            'id' => (string) Str::uuid(),
            'registration_id' => $reg->id,
            'ticket_code' => 'TKT-2026-CSVTEST',
            'qr_payload' => 'dummy',
            'is_checked_in' => false,
        ]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->get("/api/v1/organizer/events/{$this->event->id}/export/participants");

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('rekap-peserta-festival-teknologi-karir-bsi-uii-2026.csv', (string) $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringContainsString('TKT-2026-CSVTEST', $content);
        $this->assertStringContainsString($this->student->name, $content);
        $this->assertStringContainsString('21523999', $content);
    }

    public function test_organizer_can_export_applicants_csv(): void
    {
        $recruitmentEvent = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
            'title' => 'Oprec Panitia Milad UII 2026',
            'slug' => 'oprec-panitia-milad-uii-2026',
            'description' => 'Rekrutmen panitia milad.',
            'poster_url' => 'https://storage.uii.ac.id/posters/milad.jpg',
            'activity_type' => ActivityTypeEnum::COMMITTEE_RECRUITMENT,
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHours(4),
            'location' => 'Kampus Terpadu UII',
            'quota_total' => 50,
            'quota_remaining' => 50,
            'status' => EventStatusEnum::PUBLISHED,
        ]);

        Registration::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->student->id,
            'event_id' => $recruitmentEvent->id,
            'registration_type' => 'COMMITTEE',
            'division_pref_1' => 'Divisi Acara',
            'division_pref_2' => 'Divisi Humas',
            'cv_file_path' => 'recruitment_docs/dummy.pdf',
            'status' => RegistrationStatusEnum::REGISTERED,
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->get("/api/v1/organizer/events/{$recruitmentEvent->id}/export/applicants");

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('rekap-pelamar-oprec-panitia-milad-uii-2026.csv', (string) $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringContainsString('Divisi Acara', $content);
        $this->assertStringContainsString($this->student->name, $content);
    }

    public function test_idor_protection_other_organizer_forbidden_from_recap_and_export(): void
    {
        $recapRes = $this->actingAs($this->otherOrganizer, 'sanctum')
            ->getJson("/api/v1/organizer/events/{$this->event->id}/recap");

        $recapRes->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_FORBIDDEN');

        $exportRes = $this->actingAs($this->otherOrganizer, 'sanctum')
            ->get("/api/v1/organizer/events/{$this->event->id}/export/participants");

        $exportRes->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_FORBIDDEN');
    }

    public function test_organizer_can_trigger_manual_instagram_publish(): void
    {
        Config::set('services.instagram.account_id', '17841400000000001');
        Config::set('services.instagram.access_token', 'EAABdummy_meta_access_token');

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['id' => 'post_manual_789'], 200),
        ]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson("/api/v1/organizer/events/{$this->event->id}/publish-instagram");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.post_id', 'post_manual_789');
    }
}
