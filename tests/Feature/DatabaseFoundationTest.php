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
use App\Models\EventDivision;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test role assignment and user role helper methods.
     */
    public function test_user_roles_and_helpers_work_correctly(): void
    {
        $studentRole = Role::create([
            'id' => 1,
            'name' => RoleEnum::STUDENT->value,
            'label' => RoleEnum::STUDENT->label(),
        ]);

        $organizerRole = Role::create([
            'id' => 2,
            'name' => RoleEnum::ORGANIZER->value,
            'label' => RoleEnum::ORGANIZER->label(),
        ]);

        $user = User::create([
            'id' => (string) Str::uuid(),
            'nim' => '21523001',
            'name' => 'Ahmad Fauzan',
            'email' => '21523001@students.uii.ac.id',
            'password' => 'secret123',
            'phone_number' => '6281234567890',
            'faculty' => 'Fakultas Teknologi Industri',
            'study_program' => 'Informatika',
        ]);

        $user->roles()->attach($studentRole->id);

        $this->assertTrue($user->hasRole(RoleEnum::STUDENT));
        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isOrganizer());
        $this->assertFalse($user->isSuperAdmin());

        // Attach organizer role
        $user->roles()->attach($organizerRole->id);
        $user->load('roles');

        $this->assertTrue($user->isOrganizer());
    }

    /**
     * Test complete event creation, registration, ticket and certificate relationship.
     */
    public function test_event_hierarchy_and_relations_work_correctly(): void
    {
        $organizer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'LEM FTI UII',
            'email' => 'lemfti@uii.ac.id',
            'password' => 'secret123',
            'phone_number' => '6282222222222',
        ]);

        $category = EventCategory::create([
            'name' => 'Seminar',
            'slug' => 'seminar',
            'description' => 'Seminar akademik nasional',
        ]);

        $event = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $organizer->id,
            'category_id' => $category->id,
            'title' => 'Seminar Nasional AI 2026',
            'slug' => 'seminar-nasional-ai-2026',
            'description' => 'Seminar teknologi kecerdasan buatan',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://example.com/poster.jpg',
            'quota_total' => 100,
            'quota_remaining' => 99,
            'start_time' => now()->addDays(7),
            'end_time' => now()->addDays(7)->addHours(4),
            'location' => 'Auditorium FTI UII',
            'status' => EventStatusEnum::PUBLISHED,
            'auto_post_ig' => true,
        ]);

        $division = EventDivision::create([
            'event_id' => $event->id,
            'name' => 'Divisi Acara',
            'description' => 'Mengatur rundown kegiatan',
            'quota' => 5,
        ]);

        $student = User::create([
            'id' => (string) Str::uuid(),
            'nim' => '21523002',
            'name' => 'Siti Rahmawati',
            'email' => '21523002@students.uii.ac.id',
            'password' => 'secret123',
            'phone_number' => '6283333333333',
        ]);

        $registration = Registration::create([
            'id' => (string) Str::uuid(),
            'user_id' => $student->id,
            'event_id' => $event->id,
            'registration_type' => 'PARTICIPANT',
            'status' => RegistrationStatusEnum::REGISTERED,
        ]);

        $ticket = Ticket::create([
            'id' => (string) Str::uuid(),
            'registration_id' => $registration->id,
            'ticket_code' => 'TKT-2026-TEST01',
            'qr_payload' => 'dummy_qr_payload_string',
            'is_checked_in' => false,
        ]);

        $certificate = Certificate::create([
            'id' => (string) Str::uuid(),
            'registration_id' => $registration->id,
            'certificate_number' => 'UII/CERT/2026/001',
            'verify_hash' => hash('sha256', 'UII/CERT/2026/001'),
            'issued_at' => now(),
        ]);

        // Assert relationships
        $this->assertEquals($organizer->id, $event->organizer->id);
        $this->assertEquals($category->id, $event->category->id);
        $this->assertCount(1, $event->divisions);
        $this->assertCount(1, $event->registrations);
        $this->assertEquals($ticket->id, $registration->ticket->id);
        $this->assertEquals($certificate->id, $registration->certificate->id);
        $this->assertTrue($event->isPublished());
        $this->assertTrue($event->hasAvailableQuota());
    }

    /**
     * Test global response envelope on 404 error.
     */
    public function test_api_404_error_response_envelope(): void
    {
        $response = $this->getJson('/api/v1/non-existent-endpoint');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status',
                'error_code',
                'message',
                'errors',
            ])
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_NOT_FOUND',
            ]);
    }
}
