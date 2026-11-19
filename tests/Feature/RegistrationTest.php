<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityTypeEnum;
use App\Enums\EventStatusEnum;
use App\Enums\RegistrationStatusEnum;
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

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected User $organizer;
    protected Event $publishedEvent;
    protected Event $draftEvent;
    protected Event $fullEvent;

    protected string $studentToken;
    protected string $otherStudentToken;
    protected string $organizerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventCategorySeeder::class]);

        $studentRole = Role::where('name', RoleEnum::STUDENT->value)->firstOrFail();
        $organizerRole = Role::where('name', RoleEnum::ORGANIZER->value)->firstOrFail();
        $category = EventCategory::firstOrFail();

        // 1. Organizer
        $this->organizer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'LEM FTI Organizer',
            'email' => 'lemfti@uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '628111111',
        ]);
        $this->organizer->roles()->attach($organizerRole->id);
        $this->organizerToken = $this->organizer->createToken('token')->plainTextToken;

        // 2. Student 1
        $this->student = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Ahmad Fauzan',
            'nim' => '21523001',
            'email' => '21523001@students.uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '628222222',
        ]);
        $this->student->roles()->attach($studentRole->id);
        $this->studentToken = $this->student->createToken('token')->plainTextToken;

        // 3. Student 2
        $this->otherStudent = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Siti Rahmawati',
            'nim' => '21523002',
            'email' => '21523002@students.uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '628333333',
        ]);
        $this->otherStudent->roles()->attach($studentRole->id);
        $this->otherStudentToken = $this->otherStudent->createToken('token')->plainTextToken;

        // 4. Published Event with quota
        $this->publishedEvent = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $category->id,
            'title' => 'Seminar Nasional AI 2026',
            'slug' => 'seminar-nasional-ai-2026',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://storage.com/ai.jpg',
            'quota_total' => 10,
            'quota_remaining' => 10,
            'start_time' => now()->addDays(5),
            'end_time' => now()->addDays(5)->addHours(4),
            'location' => 'Auditorium FTI UII',
            'status' => EventStatusEnum::PUBLISHED,
        ]);

        // 5. Full Event (0 quota remaining)
        $this->fullEvent = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $category->id,
            'title' => 'Workshop Eksklusif Full Quota',
            'slug' => 'workshop-full-quota',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://storage.com/full.jpg',
            'quota_total' => 5,
            'quota_remaining' => 0,
            'start_time' => now()->addDays(6),
            'end_time' => now()->addDays(6)->addHours(3),
            'location' => 'Lab Komputer',
            'status' => EventStatusEnum::PUBLISHED,
        ]);

        // 6. Draft Event
        $this->draftEvent = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $category->id,
            'title' => 'Acara Masih Draf',
            'slug' => 'acara-masih-draf',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://storage.com/draft.jpg',
            'quota_total' => 20,
            'quota_remaining' => 20,
            'start_time' => now()->addDays(10),
            'end_time' => now()->addDays(10)->addHours(3),
            'location' => 'Ruang Draf',
            'status' => EventStatusEnum::DRAFT,
        ]);
    }

    /**
     * Test successful participant registration with signed QR E-Ticket.
     */
    public function test_student_can_register_and_receive_signed_e_ticket(): void
    {
        $payload = [
            'custom_answers' => [
                'tshirt_size' => 'XL',
                'dietary' => 'Halal',
            ],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->publishedEvent->id}/register", $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'ticket_code',
                    'qr_payload',
                    'is_checked_in',
                    'event' => [
                        'id',
                        'title',
                        'slug',
                    ],
                    'participant' => [
                        'name',
                        'nim',
                        'email',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'participant' => [
                        'nim' => '21523001',
                    ],
                ],
            ]);

        // Quota should decrement from 10 to 9
        $this->assertEquals(9, $this->publishedEvent->fresh()->quota_remaining);

        // Ticket code format verification
        $ticketCode = $response->json('data.ticket_code');
        $this->assertStringStartsWith('TKT-', $ticketCode);

        // QR Payload signature verification
        $qrPayloadRaw = base64_decode($response->json('data.qr_payload'));
        $qrJson = json_decode($qrPayloadRaw, true);
        $this->assertArrayHasKey('sig', $qrJson);
    }

    /**
     * Test anti-double entry guard: user cannot register twice on same event.
     */
    public function test_anti_double_entry_rejects_second_registration(): void
    {
        // 1st Registration -> 201 Created
        $res1 = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->publishedEvent->id}/register");
        $res1->assertStatus(201);

        $this->app['auth']->forgetGuards();

        // 2nd Registration on same event -> 409 Conflict
        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->publishedEvent->id}/register");

        $res2->assertStatus(409)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_ALREADY_REGISTERED',
            ]);
    }

    /**
     * Test cannot register when event quota is 0.
     */
    public function test_cannot_register_when_quota_exhausted(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->fullEvent->id}/register");

        $response->assertStatus(400);
    }

    /**
     * Test cannot register for unapproved / DRAFT event.
     */
    public function test_cannot_register_for_draft_event(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->draftEvent->id}/register");

        $response->assertStatus(400);
    }

    /**
     * Test student can view their ticket list and ticket detail.
     */
    public function test_student_can_view_my_tickets_and_detail(): void
    {
        $regResponse = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->publishedEvent->id}/register");
        $ticketCode = $regResponse->json('data.ticket_code');

        $this->app['auth']->forgetGuards();

        // 1. List my tickets
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/v1/my-tickets');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['ticket_code', 'event'],
                ],
            ]);
        $this->assertEquals(1, $listResponse->json('meta.total'));

        $this->app['auth']->forgetGuards();

        // 2. Detail ticket by code
        $detailResponse = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson("/api/v1/my-tickets/{$ticketCode}");
        $detailResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'ticket_code' => $ticketCode,
                ],
            ]);
    }

    /**
     * Test IDOR protection: Student B cannot view Student A's ticket.
     */
    public function test_student_cannot_view_other_students_ticket(): void
    {
        // Student A registers
        $regResponse = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->publishedEvent->id}/register");
        $ticketCode = $regResponse->json('data.ticket_code');

        $this->app['auth']->forgetGuards();

        // Student B attempts to access Student A's ticket
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->otherStudentToken)
            ->getJson("/api/v1/my-tickets/{$ticketCode}");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_FORBIDDEN',
            ]);
    }

    /**
     * Test student can cancel ticket, quota is restored, and can re-register.
     */
    public function test_ticket_cancellation_and_re_registration(): void
    {
        // 1. Register (Quota: 10 -> 9)
        $regResponse = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->publishedEvent->id}/register");
        $ticketCode = $regResponse->json('data.ticket_code');
        $this->assertEquals(9, $this->publishedEvent->fresh()->quota_remaining);

        $this->app['auth']->forgetGuards();

        // 2. Cancel Ticket (Quota restored: 9 -> 10)
        $cancelResponse = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/my-tickets/{$ticketCode}/cancel");

        $cancelResponse->assertStatus(200);
        $this->assertEquals(10, $this->publishedEvent->fresh()->quota_remaining);

        $this->app['auth']->forgetGuards();

        // 3. Re-register should succeed because status is CANCELLED
        $reRegResponse = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->publishedEvent->id}/register");

        $reRegResponse->assertStatus(201);
        $this->assertEquals(9, $this->publishedEvent->fresh()->quota_remaining);
    }

    /**
     * Test organizer can view attendee registrations list for their event.
     */
    public function test_organizer_can_view_event_attendees(): void
    {
        // Student registers
        $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->publishedEvent->id}/register");

        $this->app['auth']->forgetGuards();

        // Organizer retrieves attendee list
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->getJson("/api/v1/organizer/events/{$this->publishedEvent->id}/registrations");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'registration_type',
                        'status',
                        'user',
                    ],
                ],
                'meta' => [
                    'total',
                ],
            ]);

        $this->assertEquals(1, $response->json('meta.total'));
    }
}
