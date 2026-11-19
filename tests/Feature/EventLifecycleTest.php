<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityTypeEnum;
use App\Enums\EventStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class EventLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $organizer;
    protected User $otherOrganizer;
    protected User $superadmin;
    protected User $student;
    protected EventCategory $category;

    protected string $organizerToken;
    protected string $otherOrganizerToken;
    protected string $superadminToken;
    protected string $studentToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventCategorySeeder::class]);

        $organizerRole = Role::where('name', RoleEnum::ORGANIZER->value)->firstOrFail();
        $superadminRole = Role::where('name', RoleEnum::SUPERADMIN->value)->firstOrFail();
        $studentRole = Role::where('name', RoleEnum::STUDENT->value)->firstOrFail();

        $this->category = EventCategory::firstOrFail();

        // 1. Organizer 1 (LEM FTI)
        $this->organizer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'LEM FTI Organizer',
            'email' => 'lemfti@uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '628111111',
        ]);
        $this->organizer->roles()->attach($organizerRole->id);
        $this->organizerToken = $this->organizer->createToken('token')->plainTextToken;

        // 2. Organizer 2 (DPM FE)
        $this->otherOrganizer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'LEM FE Organizer',
            'email' => 'lemfe@uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '628222222',
        ]);
        $this->otherOrganizer->roles()->attach($organizerRole->id);
        $this->otherOrganizerToken = $this->otherOrganizer->createToken('token')->plainTextToken;

        // 3. Superadmin (DPM UII)
        $this->superadmin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Admin DPM',
            'email' => 'admin.dpm@uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '628333333',
        ]);
        $this->superadmin->roles()->attach($superadminRole->id);
        $this->superadminToken = $this->superadmin->createToken('token')->plainTextToken;

        // 4. Student
        $this->student = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Student User',
            'email' => 'student@uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '628444444',
        ]);
        $this->student->roles()->attach($studentRole->id);
        $this->studentToken = $this->student->createToken('token')->plainTextToken;
    }

    /**
     * Test organizer can create a new draft event.
     */
    public function test_organizer_can_create_draft_event(): void
    {
        $payload = [
            'title' => 'Lomba Karya Tulis Ilmiah Nasional 2026',
            'category_id' => $this->category->id,
            'description' => 'Kompetisi karya tulis ilmiah mahasiswa se-Indonesia.',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT->value,
            'poster_url' => 'https://storage.uii-event.id/posters/lkti-2026.jpg',
            'quota_total' => 150,
            'start_time' => now()->addDays(14)->toISOString(),
            'end_time' => now()->addDays(14)->addHours(8)->toISOString(),
            'location' => 'Gedung Kuliah Umum Prof. Dr. Sardjito Kampus Terpadu UII',
            'auto_post_ig' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson('/api/v1/organizer/events', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'title' => 'Lomba Karya Tulis Ilmiah Nasional 2026',
                    'status' => 'DRAFT',
                    'quota_total' => 150,
                    'quota_remaining' => 150,
                ],
            ]);

        $this->assertDatabaseHas('events', [
            'title' => 'Lomba Karya Tulis Ilmiah Nasional 2026',
            'organizer_id' => $this->organizer->id,
            'status' => 'DRAFT',
        ]);
    }

    /**
     * Test organizer can create oprec event with divisions.
     */
    public function test_organizer_can_create_recruitment_event_with_divisions(): void
    {
        $payload = [
            'title' => 'Oprec Panitia Pensi Milad Kampus 2026',
            'category_id' => $this->category->id,
            'description' => 'Perekrutan panitia pentas seni akbar tahunan UII.',
            'activity_type' => ActivityTypeEnum::COMMITTEE_RECRUITMENT->value,
            'poster_url' => 'https://storage.uii-event.id/posters/pensi.jpg',
            'quota_total' => 80,
            'start_time' => now()->addDays(20)->toISOString(),
            'end_time' => now()->addDays(20)->addHours(6)->toISOString(),
            'location' => 'Hall Gedung Student Center UII',
            'divisions' => [
                ['name' => 'Divisi Acara', 'quota' => 25, 'description' => 'Konseptor rundown'],
                ['name' => 'Divisi Dokumentasi', 'quota' => 15, 'description' => 'Fotografi & video'],
                ['name' => 'Divisi Konsumsi', 'quota' => 40, 'description' => 'Logistik katering'],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson('/api/v1/organizer/events', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'divisions' => [
                        '*' => ['id', 'name', 'quota', 'description'],
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data.divisions'));
    }

    /**
     * Test IDOR protection: Organizer A cannot update Organizer B's event.
     */
    public function test_organizer_cannot_update_other_organizers_event(): void
    {
        // Event created by Organizer 1
        $event = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
            'title' => 'Event Milik LEM FTI',
            'slug' => 'event-lem-fti-01',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://storage.com/poster.jpg',
            'quota_total' => 100,
            'quota_remaining' => 100,
            'start_time' => now()->addDays(10),
            'end_time' => now()->addDays(10)->addHours(4),
            'location' => 'Auditorium FTI',
            'status' => EventStatusEnum::DRAFT,
        ]);

        // Organizer 2 attempts to update Organizer 1's event
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->otherOrganizerToken)
            ->putJson("/api/v1/organizer/events/{$event->id}", [
                'title' => 'Judul Dibajak Oleh Organizer Lain',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_FORBIDDEN',
            ]);
    }

    /**
     * Test full lifecycle: Draft -> Submit -> Approve -> Appears on Public Catalog.
     */
    public function test_event_approval_lifecycle_to_published(): void
    {
        // 1. Create Draft
        $event = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
            'title' => 'Simposium Teknologi Web 2026',
            'slug' => 'simposium-web-2026',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://storage.com/web.jpg',
            'quota_total' => 50,
            'quota_remaining' => 50,
            'start_time' => now()->addDays(7),
            'end_time' => now()->addDays(7)->addHours(3),
            'location' => 'Ruang Seminar FTI',
            'status' => EventStatusEnum::DRAFT,
        ]);

        // 2. Organizer submits event for approval
        $submitRes = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson("/api/v1/organizer/events/{$event->id}/submit");

        $submitRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'PENDING_APPROVAL',
                ],
            ]);

        $this->app['auth']->forgetGuards();

        // 3. Superadmin approves event
        $approveRes = $this->withHeader('Authorization', 'Bearer ' . $this->superadminToken)
            ->postJson("/api/v1/admin/events/{$event->id}/approve");

        $approveRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'PUBLISHED',
                ],
            ]);

        // 4. Verify event is now visible to public
        $publicRes = $this->getJson('/api/v1/events/simposium-web-2026');
        $publicRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'slug' => 'simposium-web-2026',
                    'status' => 'PUBLISHED',
                ],
            ]);
    }

    /**
     * Test rejection workflow: Submit -> Reject with Reason -> Organizer can re-edit.
     */
    public function test_event_rejection_workflow(): void
    {
        $event = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
            'title' => 'Acara Bermasalah',
            'slug' => 'acara-bermasalah',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://storage.com/bad.jpg',
            'quota_total' => 50,
            'quota_remaining' => 50,
            'start_time' => now()->addDays(5),
            'end_time' => now()->addDays(5)->addHours(2),
            'location' => 'Aula UII',
            'status' => EventStatusEnum::PENDING_APPROVAL,
        ]);

        // Superadmin rejects with reason
        $rejectRes = $this->withHeader('Authorization', 'Bearer ' . $this->superadminToken)
            ->postJson("/api/v1/admin/events/{$event->id}/reject", [
                'rejection_reason' => 'Poster melanggar pedoman visual kampus dan rundown belum ada.',
            ]);

        $rejectRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'REJECTED',
                ],
            ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'REJECTED',
            'rejection_reason' => 'Poster melanggar pedoman visual kampus dan rundown belum ada.',
        ]);

        $this->app['auth']->forgetGuards();

        // Organizer can update the rejected event
        $updateRes = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->putJson("/api/v1/organizer/events/{$event->id}", [
                'title' => 'Acara Sudah Direvisi Sesuai Panduan',
            ]);

        $updateRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Acara Sudah Direvisi Sesuai Panduan',
                ],
            ]);
    }

    /**
     * Test student cannot access organizer or admin management routes.
     */
    public function test_student_forbidden_from_organizer_and_admin_endpoints(): void
    {
        // 1. Student accesses /organizer/events -> 403 Forbidden
        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/v1/organizer/events');
        $res1->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_FORBIDDEN',
            ]);

        $this->app['auth']->forgetGuards();

        // 2. Student accesses /admin/events -> 403 Forbidden
        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/v1/admin/events');
        $res2->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_FORBIDDEN',
            ]);
    }
}
