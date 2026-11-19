<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthAndRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Test successful student registration.
     */
    public function test_user_can_register_as_student(): void
    {
        $payload = [
            'name' => 'Muhammad Budi Santoso',
            'email' => '21523099@students.uii.ac.id',
            'password' => 'PasswordRahasia123',
            'password_confirmation' => 'PasswordRahasia123',
            'phone_number' => '6281299998888',
            'nim' => '21523099',
            'faculty' => 'Fakultas Teknologi Industri',
            'study_program' => 'Informatika',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'nim',
                        'name',
                        'email',
                        'phone_number',
                        'roles',
                    ],
                    'token',
                    'token_type',
                ],
                'meta',
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'user' => [
                        'email' => '21523099@students.uii.ac.id',
                        'nim' => '21523099',
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => '21523099@students.uii.ac.id',
            'nim' => '21523099',
        ]);
    }

    /**
     * Test registration fails on duplicate email or password confirmation mismatch.
     */
    public function test_registration_fails_on_validation_errors(): void
    {
        User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Existing User',
            'email' => 'existing@students.uii.ac.id',
            'password' => Hash::make('secret123'),
            'phone_number' => '6281111111',
        ]);

        $payload = [
            'name' => 'New User',
            'email' => 'existing@students.uii.ac.id',
            'password' => 'pass1234',
            'password_confirmation' => 'mismatchPass',
            'phone_number' => '628222222',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_VALIDATION',
            ]);
    }

    /**
     * Test user cannot self-register as SUPERADMIN.
     */
    public function test_user_cannot_self_register_as_superadmin(): void
    {
        $payload = [
            'name' => 'Hacker Admin',
            'email' => 'fakeadmin@uii.ac.id',
            'password' => 'PasswordRahasia123',
            'password_confirmation' => 'PasswordRahasia123',
            'phone_number' => '62899999999',
            'role' => RoleEnum::SUPERADMIN->value,
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_VALIDATION',
            ]);
    }

    /**
     * Test login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Sarah Nabila',
            'email' => 'lemfti@uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '6282222222222',
        ]);
        $organizerRole = Role::where('name', RoleEnum::ORGANIZER->value)->firstOrFail();
        $user->roles()->attach($organizerRole->id);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'lemfti@uii.ac.id',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user',
                    'token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'user' => [
                        'email' => 'lemfti@uii.ac.id',
                    ],
                ],
            ]);
    }

    /**
     * Test login fails with invalid password.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Sarah Nabila',
            'email' => 'lemfti@uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '6282222222222',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'lemfti@uii.ac.id',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_VALIDATION',
            ]);
    }

    /**
     * Test authenticated profile (/auth/me) and unauthenticated rejection.
     */
    public function test_profile_requires_authentication(): void
    {
        // Unauthenticated request
        $unauthResponse = $this->getJson('/api/v1/auth/me');
        $unauthResponse->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_UNAUTHENTICATED',
            ]);

        // Authenticated request
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Ahmad Fauzan',
            'email' => '21523001@students.uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '62812345678',
        ]);
        $studentRole = Role::where('name', RoleEnum::STUDENT->value)->firstOrFail();
        $user->roles()->attach($studentRole->id);

        $token = $user->createToken('test_token')->plainTextToken;

        $authResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $authResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'email' => '21523001@students.uii.ac.id',
                    'name' => 'Ahmad Fauzan',
                ],
            ]);
    }

    /**
     * Test logout revokes token.
     */
    public function test_logout_revokes_token(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Ahmad Fauzan',
            'email' => '21523001@students.uii.ac.id',
            'password' => Hash::make('Password123!'),
            'phone_number' => '62812345678',
        ]);

        $token = $user->createToken('logout_token')->plainTextToken;

        $this->assertEquals(1, $user->tokens()->count());

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * Test RBAC middleware rejects unauthorized roles and allows authorized roles.
     */
    public function test_rbac_role_middleware_access_control(): void
    {
        $studentRole = Role::where('name', RoleEnum::STUDENT->value)->firstOrFail();
        $organizerRole = Role::where('name', RoleEnum::ORGANIZER->value)->firstOrFail();
        $superadminRole = Role::where('name', RoleEnum::SUPERADMIN->value)->firstOrFail();

        $student = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Student User',
            'email' => 'student@uii.ac.id',
            'password' => Hash::make('secret123'),
            'phone_number' => '6281111',
        ]);
        $student->roles()->attach($studentRole->id);

        $organizer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Organizer User',
            'email' => 'organizer@uii.ac.id',
            'password' => Hash::make('secret123'),
            'phone_number' => '6282222',
        ]);
        $organizer->roles()->attach($organizerRole->id);

        $superadmin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Superadmin User',
            'email' => 'superadmin@uii.ac.id',
            'password' => Hash::make('secret123'),
            'phone_number' => '6283333',
        ]);
        $superadmin->roles()->attach($superadminRole->id);

        $studentToken = $student->createToken('token')->plainTextToken;
        $organizerToken = $organizer->createToken('token')->plainTextToken;
        $superadminToken = $superadmin->createToken('token')->plainTextToken;

        // 1. Student accesses organizer-only route -> 403 Forbidden
        $response = $this->withHeader('Authorization', 'Bearer ' . $studentToken)
            ->getJson('/api/v1/test/organizer-only');
        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_FORBIDDEN',
            ]);

        // Reset auth guard between requests in the same test
        $this->app['auth']->forgetGuards();

        // 2. Organizer accesses organizer-only route -> 200 OK
        $response = $this->withHeader('Authorization', 'Bearer ' . $organizerToken)
            ->getJson('/api/v1/test/organizer-only');
        $response->assertStatus(200);

        $this->app['auth']->forgetGuards();

        // 3. Organizer accesses superadmin-only route -> 403 Forbidden
        $response = $this->withHeader('Authorization', 'Bearer ' . $organizerToken)
            ->getJson('/api/v1/test/superadmin-only');
        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'error_code' => 'ERR_FORBIDDEN',
            ]);

        $this->app['auth']->forgetGuards();

        // 4. Superadmin accesses superadmin-only route -> 200 OK
        $response = $this->withHeader('Authorization', 'Bearer ' . $superadminToken)
            ->getJson('/api/v1/test/superadmin-only');
        $response->assertStatus(200);
    }
}
