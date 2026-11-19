<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityTypeEnum;
use App\Enums\EventStatusEnum;
use App\Enums\RegistrationStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected User $organizer;
    protected User $otherOrganizer;
    protected Event $event;
    protected Event $otherEvent;
    protected string $studentToken;
    protected string $otherStudentToken;
    protected string $organizerToken;
    protected string $otherOrganizerToken;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $studentRole = Role::create([
            'id' => (string) Str::uuid(),
            'name' => RoleEnum::STUDENT->value,
            'description' => 'Mahasiswa',
        ]);

        $organizerRole = Role::create([
            'id' => (string) Str::uuid(),
            'name' => RoleEnum::ORGANIZER->value,
            'description' => 'Panitia',
        ]);

        $category = EventCategory::create([
            'name' => 'Seminar',
            'slug' => 'seminar',
            'icon' => 'mic',
        ]);

        // 1. Setup Student 1 & 2
        $this->student = User::factory()->create([
            'name' => 'Fauzan Mahasiswa',
            'email' => 'fauzan@students.uii.ac.id',
            'nim' => '21523001',
            'faculty' => 'Fakultas Teknologi Industri',
            'study_program' => 'Informatika',
        ]);
        $this->student->roles()->attach($studentRole->id);
        $this->studentToken = $this->student->createToken('test_student')->plainTextToken;

        $this->otherStudent = User::factory()->create([
            'name' => 'Budi Mahasiswa',
            'email' => 'budi@students.uii.ac.id',
            'nim' => '21523002',
        ]);
        $this->otherStudent->roles()->attach($studentRole->id);
        $this->otherStudentToken = $this->otherStudent->createToken('test_other_student')->plainTextToken;

        // 2. Setup Organizer 1 & 2
        $this->organizer = User::factory()->create([
            'name' => 'Panitia LEM FTI',
            'email' => 'lemfti@uii.ac.id',
        ]);
        $this->organizer->roles()->attach($organizerRole->id);
        $this->organizerToken = $this->organizer->createToken('test_org')->plainTextToken;

        $this->otherOrganizer = User::factory()->create([
            'name' => 'Panitia HMTI',
            'email' => 'hmti@uii.ac.id',
        ]);
        $this->otherOrganizer->roles()->attach($organizerRole->id);
        $this->otherOrganizerToken = $this->otherOrganizer->createToken('test_other_org')->plainTextToken;

        // 3. Setup Published Event for Organizer 1
        $this->event = Event::create([
            'id' => (string) Str::uuid(),
            'title' => 'Seminar Nasional AI 2026',
            'slug' => 'seminar-nasional-ai-2026',
            'category_id' => $category->id,
            'organizer_id' => $this->organizer->id,
            'description' => 'Seminar AI hari-H presensi.',
            'poster_url' => 'https://storage.uii.ac.id/posters/ai.jpg',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'status' => EventStatusEnum::PUBLISHED,
            'quota_total' => 100,
            'quota_remaining' => 99,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(4),
            'location' => 'Auditorium FTI UII',
        ]);

        // 4. Setup Other Event for Organizer 2
        $this->otherEvent = Event::create([
            'id' => (string) Str::uuid(),
            'title' => 'Workshop Desain Grafis',
            'slug' => 'workshop-desain-grafis',
            'category_id' => $category->id,
            'organizer_id' => $this->otherOrganizer->id,
            'description' => 'Workshop panitia lain.',
            'poster_url' => 'https://storage.uii.ac.id/posters/design.jpg',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'status' => EventStatusEnum::PUBLISHED,
            'quota_total' => 50,
            'quota_remaining' => 50,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(3),
            'location' => 'Lab Desain UII',
        ]);

        // 5. Create Registration & Signed Ticket for Student 1
        $registration = Registration::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->student->id,
            'event_id' => $this->event->id,
            'registration_type' => 'PARTICIPANT',
            'status' => RegistrationStatusEnum::REGISTERED,
            'registered_at' => now(),
        ]);

        $ticketCode = 'TKT-2026-TESTING1';
        $signature = hash_hmac(
            'sha256',
            "{$ticketCode}|{$this->student->id}|{$this->event->id}",
            config('app.key')
        );

        $qrPayload = base64_encode(json_encode([
            'ticket_code' => $ticketCode,
            'reg_id' => $registration->id,
            'user_id' => $this->student->id,
            'event_id' => $this->event->id,
            'sig' => $signature,
        ], JSON_THROW_ON_ERROR));

        $this->ticket = Ticket::create([
            'id' => (string) Str::uuid(),
            'registration_id' => $registration->id,
            'ticket_code' => $ticketCode,
            'qr_payload' => $qrPayload,
            'is_checked_in' => false,
        ]);
    }

    public function test_organizer_can_check_in_attendee_with_valid_qr_and_receive_certificate(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson('/api/v1/attendance/check-in', [
                'qr_payload' => $this->ticket->qr_payload,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'ticket_code' => $this->ticket->ticket_code,
                    'is_checked_in' => true,
                    'attendee' => [
                        'id' => $this->student->id,
                        'name' => 'Fauzan Mahasiswa',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'certificate' => [
                        'certificate_number',
                        'verify_hash',
                        'verify_url',
                        'status',
                    ],
                ],
            ]);

        // Verify ticket marked checked in
        $this->assertTrue($this->ticket->fresh()->is_checked_in);
        $this->assertEquals($this->organizer->id, $this->ticket->fresh()->checked_in_by);

        // Verify registration status upgraded to ATTENDED
        $this->assertEquals(RegistrationStatusEnum::ATTENDED, $this->ticket->registration->fresh()->status);

        // Verify Certificate database record created
        $this->assertDatabaseHas('certificates', [
            'registration_id' => $this->ticket->registration_id,
        ]);
    }

    public function test_anti_double_check_in_guard(): void
    {
        // First check in succeeds
        $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson('/api/v1/attendance/check-in', [
                'qr_payload' => $this->ticket->qr_payload,
            ])->assertStatus(200);

        // Second check in rejected
        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson('/api/v1/attendance/check-in', [
                'qr_payload' => $this->ticket->qr_payload,
            ]);

        $res2->assertStatus(409)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_ALREADY_CHECKED_IN',
            ]);
    }

    public function test_rejects_forged_or_tampered_qr_signature(): void
    {
        // Forged payload with modified signature
        $tamperedPayload = base64_encode(json_encode([
            'ticket_code' => $this->ticket->ticket_code,
            'reg_id' => $this->ticket->registration_id,
            'user_id' => $this->student->id,
            'event_id' => $this->event->id,
            'sig' => 'fake_tampered_signature_9999999999999999999999999999999999999999',
        ]));

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson('/api/v1/attendance/check-in', [
                'qr_payload' => $tamperedPayload,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_INVALID_QR',
            ]);
    }

    public function test_idor_protection_organizer_cannot_scan_tickets_from_other_events(): void
    {
        // Organizer 2 attempts to scan Organizer 1's ticket
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->otherOrganizerToken)
            ->postJson('/api/v1/attendance/check-in', [
                'qr_payload' => $this->ticket->qr_payload,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_FORBIDDEN',
            ]);
    }

    public function test_student_forbidden_from_calling_check_in_scanner_endpoint(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson('/api/v1/attendance/check-in', [
                'qr_payload' => $this->ticket->qr_payload,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_FORBIDDEN',
            ]);
    }

    public function test_organizer_can_view_event_attendance_list_and_statistics(): void
    {
        // Check in Student 1
        $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson('/api/v1/attendance/check-in', [
                'qr_payload' => $this->ticket->qr_payload,
            ])->assertStatus(200);

        // Student 2 is registered but absent
        Registration::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->otherStudent->id,
            'event_id' => $this->event->id,
            'registration_type' => 'PARTICIPANT',
            'status' => RegistrationStatusEnum::REGISTERED,
            'registered_at' => now(),
        ]);

        Ticket::create([
            'id' => (string) Str::uuid(),
            'registration_id' => Registration::where('user_id', $this->otherStudent->id)->first()->id,
            'ticket_code' => 'TKT-2026-ABSENT1',
            'qr_payload' => 'dummy_payload',
            'is_checked_in' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->getJson("/api/v1/organizer/events/{$this->event->id}/attendance");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'meta' => [
                    'statistics' => [
                        'total_registered' => 2,
                        'total_attended' => 1,
                        'total_absent' => 1,
                        'attendance_rate_percent' => 50.0,
                    ],
                ],
            ]);
    }

    public function test_public_can_verify_certificate_by_hash(): void
    {
        // 1. Perform check-in to generate certificate
        $checkInRes = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson('/api/v1/attendance/check-in', [
                'qr_payload' => $this->ticket->qr_payload,
            ])->assertStatus(200);

        $verifyHash = $checkInRes->json('data.certificate.verify_hash');
        $this->assertNotNull($verifyHash);

        $this->app['auth']->forgetGuards();

        // 2. Public verification request (unauthenticated)
        $verifyRes = $this->getJson("/api/v1/certificates/verify/{$verifyHash}");

        $verifyRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'verify_hash' => $verifyHash,
                    'status' => 'VALID',
                    'recipient' => [
                        'name' => 'Fauzan Mahasiswa',
                        'nim' => '21523001',
                    ],
                    'event' => [
                        'title' => 'Seminar Nasional AI 2026',
                    ],
                ],
            ]);
    }

    public function test_invalid_certificate_hash_returns_404(): void
    {
        $response = $this->getJson('/api/v1/certificates/verify/non_existent_fake_hash_12345');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_NOT_FOUND',
            ]);
    }

    public function test_student_can_view_my_earned_certificates(): void
    {
        // 1. Check in to earn certificate
        $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson('/api/v1/attendance/check-in', [
                'qr_payload' => $this->ticket->qr_payload,
            ])->assertStatus(200);

        $this->app['auth']->forgetGuards();

        // 2. Student views their certificates
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/v1/my-certificates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'certificate_number',
                        'verify_hash',
                        'verify_url',
                        'event' => ['title'],
                        'status',
                    ],
                ],
                'meta' => ['total'],
            ]);

        $this->assertEquals(1, $response->json('meta.total'));
    }
}
