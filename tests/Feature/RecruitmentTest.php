<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityTypeEnum;
use App\Enums\EventStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\SelectionStatusEnum;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventDivision;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected User $organizer;
    protected User $otherOrganizer;
    protected Event $recruitmentEvent;
    protected Event $participantEvent;
    protected string $studentToken;
    protected string $otherStudentToken;
    protected string $organizerToken;
    protected string $otherOrganizerToken;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

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
            'name' => 'Kepanitiaan',
            'slug' => 'kepanitiaan',
            'icon' => 'users',
        ]);

        // 1. Setup Student 1 & 2
        $this->student = User::factory()->create([
            'name' => 'Fauzan Mahasiswa',
            'email' => 'fauzan@students.uii.ac.id',
            'nim' => '21523001',
        ]);
        $this->student->roles()->attach($studentRole->id);
        $this->studentToken = $this->student->createToken('test_token')->plainTextToken;

        $this->otherStudent = User::factory()->create([
            'name' => 'Budi Mahasiswa',
            'email' => 'budi@students.uii.ac.id',
            'nim' => '21523002',
        ]);
        $this->otherStudent->roles()->attach($studentRole->id);
        $this->otherStudentToken = $this->otherStudent->createToken('test_token')->plainTextToken;

        // 2. Setup Organizer 1 & 2
        $this->organizer = User::factory()->create([
            'name' => 'Panitia LEM FTI',
            'email' => 'lemfti@uii.ac.id',
        ]);
        $this->organizer->roles()->attach($organizerRole->id);
        $this->organizerToken = $this->organizer->createToken('test_token')->plainTextToken;

        $this->otherOrganizer = User::factory()->create([
            'name' => 'Panitia HMTI',
            'email' => 'hmti@uii.ac.id',
        ]);
        $this->otherOrganizer->roles()->attach($organizerRole->id);
        $this->otherOrganizerToken = $this->otherOrganizer->createToken('test_token')->plainTextToken;

        // 3. Setup Published Committee Recruitment Event with Divisions
        $this->recruitmentEvent = Event::create([
            'id' => (string) Str::uuid(),
            'title' => 'Open Recruitment Panitia Milad UII Ke-83',
            'slug' => 'oprec-milad-uii-83',
            'category_id' => $category->id,
            'organizer_id' => $this->organizer->id,
            'description' => 'Pendaftaran panitia milad.',
            'poster_url' => 'https://storage.uii.ac.id/posters/oprec-milad.jpg',
            'activity_type' => ActivityTypeEnum::COMMITTEE_RECRUITMENT,
            'status' => EventStatusEnum::PUBLISHED,
            'quota_total' => 50,
            'quota_remaining' => 50,
            'start_time' => now()->addDays(10),
            'end_time' => now()->addDays(20),
            'location' => 'Kampus Terpadu UII',
        ]);

        EventDivision::create([
            'event_id' => $this->recruitmentEvent->id,
            'name' => 'Divisi Acara',
            'quota' => 15,
        ]);

        EventDivision::create([
            'event_id' => $this->recruitmentEvent->id,
            'name' => 'Divisi Publikasi & Dokumentasi',
            'quota' => 10,
        ]);

        EventDivision::create([
            'event_id' => $this->recruitmentEvent->id,
            'name' => 'Divisi Logistik',
            'quota' => 15,
        ]);

        // 4. Setup Regular Participant Event
        $this->participantEvent = Event::create([
            'id' => (string) Str::uuid(),
            'title' => 'Seminar Biasa',
            'slug' => 'seminar-biasa',
            'category_id' => $category->id,
            'organizer_id' => $this->organizer->id,
            'description' => 'Seminar umum.',
            'poster_url' => 'https://storage.uii.ac.id/posters/seminar.jpg',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'status' => EventStatusEnum::PUBLISHED,
            'quota_total' => 100,
            'quota_remaining' => 100,
            'start_time' => now()->addDays(5),
            'end_time' => now()->addDays(6),
            'location' => 'Auditorium UII',
        ]);
    }

    public function test_student_can_apply_for_recruitment_with_valid_pdf_cv(): void
    {
        $cvFile = UploadedFile::fake()->createWithContent('curriculum_vitae.pdf', "%PDF-1.5 Sample CV Content");

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->recruitmentEvent->id}/apply", [
                'division_pref_1' => 'Divisi Acara',
                'division_pref_2' => 'Divisi Logistik',
                'cv_file' => $cvFile,
                'custom_answers' => [
                    'motivation' => 'Ingin berkontribusi aktif dalam milad kampus.',
                    'portfolio_url' => 'https://github.com/student',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'registration_type' => 'COMMITTEE',
                    'division_pref_1' => 'Divisi Acara',
                    'division_pref_2' => 'Divisi Logistik',
                    'selection_status' => SelectionStatusEnum::SUBMITTED->value,
                    'has_cv' => true,
                ],
            ]);

        // Verify database state
        $this->assertDatabaseHas('registrations', [
            'user_id' => $this->student->id,
            'event_id' => $this->recruitmentEvent->id,
            'registration_type' => 'COMMITTEE',
            'division_pref_1' => 'Divisi Acara',
            'selection_status' => SelectionStatusEnum::SUBMITTED->value,
        ]);

        // Verify quota decrement
        $this->assertEquals(49, $this->recruitmentEvent->fresh()->quota_remaining);
    }

    public function test_anti_double_entry_rejects_second_application(): void
    {
        $cv1 = UploadedFile::fake()->createWithContent('cv1.pdf', "%PDF-1.4 Content");
        $cv2 = UploadedFile::fake()->createWithContent('cv2.pdf', "%PDF-1.4 Content");

        $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->recruitmentEvent->id}/apply", [
                'division_pref_1' => 'Divisi Acara',
                'cv_file' => $cv1,
            ])->assertStatus(201);

        // Try applying again
        $res2 = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->recruitmentEvent->id}/apply", [
                'division_pref_1' => 'Divisi Logistik',
                'cv_file' => $cv2,
            ]);

        $res2->assertStatus(409)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_ALREADY_REGISTERED',
            ]);
    }

    public function test_rejects_cv_with_spoofed_or_invalid_magic_bytes(): void
    {
        // File has .pdf extension, but contains PHP / plain text bytes (no %PDF header)
        $spoofedCv = UploadedFile::fake()->createWithContent('malicious.pdf', "<?php phpinfo(); ?>");

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->recruitmentEvent->id}/apply", [
                'division_pref_1' => 'Divisi Acara',
                'cv_file' => $spoofedCv,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_VALIDATION',
            ]);
    }

    public function test_rejects_invalid_division_selection(): void
    {
        $cvFile = UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.5 Content");

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->recruitmentEvent->id}/apply", [
                'division_pref_1' => 'Divisi Antariksa Fiktif',
                'cv_file' => $cvFile,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_apply_recruitment_on_participant_event(): void
    {
        $cvFile = UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.5 Content");

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->participantEvent->id}/apply", [
                'division_pref_1' => 'Divisi Acara',
                'cv_file' => $cvFile,
            ]);

        $response->assertStatus(400);
    }

    public function test_student_can_view_my_applications_history(): void
    {
        $cvFile = UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.5 Content");

        $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->recruitmentEvent->id}/apply", [
                'division_pref_1' => 'Divisi Acara',
                'cv_file' => $cvFile,
            ])->assertStatus(201);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/v1/my-applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'registration_type',
                        'division_pref_1',
                        'selection_status',
                        'event' => ['id', 'title'],
                    ],
                ],
                'meta' => ['total'],
            ]);

        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_organizer_can_view_applicants_and_filter_by_division(): void
    {
        // Student 1 applies to Divisi Acara
        $cv1 = UploadedFile::fake()->createWithContent('cv1.pdf', "%PDF-1.5 Content 1");
        $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->recruitmentEvent->id}/apply", [
                'division_pref_1' => 'Divisi Acara',
                'cv_file' => $cv1,
            ])->assertStatus(201);

        $this->app['auth']->forgetGuards();

        // Student 2 applies to Divisi Logistik
        $cv2 = UploadedFile::fake()->createWithContent('cv2.pdf', "%PDF-1.5 Content 2");
        $this->withHeader('Authorization', 'Bearer ' . $this->otherStudentToken)
            ->postJson("/api/v1/events/{$this->recruitmentEvent->id}/apply", [
                'division_pref_1' => 'Divisi Logistik',
                'cv_file' => $cv2,
            ])->assertStatus(201);

        $this->app['auth']->forgetGuards();

        // Organizer views all applicants
        $allRes = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->getJson("/api/v1/organizer/events/{$this->recruitmentEvent->id}/applicants");

        $allRes->assertStatus(200);
        $this->assertEquals(2, $allRes->json('meta.total'));

        // Organizer filters by Divisi Acara
        $filterRes = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->getJson("/api/v1/organizer/events/{$this->recruitmentEvent->id}/applicants?division=Divisi Acara");

        $filterRes->assertStatus(200);
        $this->assertEquals(1, $filterRes->json('meta.total'));
        $this->assertEquals('Divisi Acara', $filterRes->json('data.0.division_pref_1'));
    }

    public function test_idor_protection_other_organizer_forbidden_from_viewing_applicants(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->otherOrganizerToken)
            ->getJson("/api/v1/organizer/events/{$this->recruitmentEvent->id}/applicants");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_FORBIDDEN',
            ]);
    }

    public function test_organizer_can_update_selection_status_and_notes(): void
    {
        $cv = UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.5 Content");
        $applyRes = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->recruitmentEvent->id}/apply", [
                'division_pref_1' => 'Divisi Acara',
                'cv_file' => $cv,
            ]);

        $registrationId = $applyRes->json('data.id');

        $this->app['auth']->forgetGuards();

        // Update to INTERVIEW_SCHEDULED
        $patchRes = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->patchJson("/api/v1/organizer/registrations/{$registrationId}/selection", [
                'selection_status' => SelectionStatusEnum::INTERVIEW_SCHEDULED->value,
                'notes' => 'Jadwal wawancara: Sabtu, 25 Nov pukul 10.00 WIB via Zoom.',
            ]);

        $patchRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'selection_status' => SelectionStatusEnum::INTERVIEW_SCHEDULED->value,
                    'custom_answers' => [
                        'selection_notes' => 'Jadwal wawancara: Sabtu, 25 Nov pukul 10.00 WIB via Zoom.',
                    ],
                ],
            ]);

        // Verify update to ACCEPTED
        $acceptRes = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->patchJson("/api/v1/organizer/registrations/{$registrationId}/selection", [
                'selection_status' => SelectionStatusEnum::ACCEPTED->value,
                'notes' => 'Selamat, Anda diterima di Divisi Acara Milad UII.',
            ]);

        $acceptRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'selection_status' => SelectionStatusEnum::ACCEPTED->value,
                ],
            ]);
    }

    public function test_cv_download_access_control_and_streaming(): void
    {
        $cv = UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.5 Real PDF Content");
        $applyRes = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson("/api/v1/events/{$this->recruitmentEvent->id}/apply", [
                'division_pref_1' => 'Divisi Acara',
                'cv_file' => $cv,
            ]);

        $registrationId = $applyRes->json('data.id');

        $this->app['auth']->forgetGuards();

        // 1. Applicant student can download their CV
        $studentDownload = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->get("/api/v1/registrations/{$registrationId}/cv");
        $studentDownload->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');

        $this->app['auth']->forgetGuards();

        // 2. Event organizer can download applicant CV
        $organizerDownload = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->get("/api/v1/registrations/{$registrationId}/cv");
        $organizerDownload->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');

        $this->app['auth']->forgetGuards();

        // 3. Other unrelated student CANNOT download (Forbidden 403)
        $otherDownload = $this->withHeader('Authorization', 'Bearer ' . $this->otherStudentToken)
            ->get("/api/v1/registrations/{$registrationId}/cv");
        $otherDownload->assertStatus(403);
    }
}
