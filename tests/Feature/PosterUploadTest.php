<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosterUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $organizer;
    protected User $student;
    protected string $organizerToken;
    protected string $studentToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $organizerRole = Role::where('name', RoleEnum::ORGANIZER->value)->firstOrFail();
        $studentRole = Role::where('name', RoleEnum::STUDENT->value)->firstOrFail();

        $this->organizer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'LEM FTI Organizer',
            'email' => 'lemfti@uii.ac.id',
            'password' => bcrypt('Password123!'),
            'phone_number' => '628111111',
        ]);
        $this->organizer->roles()->attach($organizerRole->id);
        $this->organizerToken = $this->organizer->createToken('token')->plainTextToken;

        $this->student = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Student User',
            'email' => 'student@uii.ac.id',
            'password' => bcrypt('Password123!'),
            'phone_number' => '628222222',
        ]);
        $this->student->roles()->attach($studentRole->id);
        $this->studentToken = $this->student->createToken('token')->plainTextToken;
    }

    public function test_organizer_can_upload_valid_poster_image(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('poster.jpg', 800, 600);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->post('/api/v1/organizer/upload-poster', [
                'poster' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Poster kegiatan berhasil diunggah.',
            ])
            ->assertJsonStructure([
                'data' => ['path', 'url', 'filename', 'size_bytes']
            ]);

        $path = $response->json('data.path');
        Storage::disk('public')->assertExists($path);
    }

    public function test_rejects_non_image_file_for_poster(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->organizerToken)
            ->postJson('/api/v1/organizer/upload-poster', [
                'poster' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_VALIDATION',
            ]);
        $this->assertEquals('poster', $response->json('errors.0.field'));
    }

    public function test_student_forbidden_from_uploading_poster(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('poster.png');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson('/api/v1/organizer/upload-poster', [
                'poster' => $file,
            ]);

        $response->assertStatus(403);
    }
}
