<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user and issue an API token.
     *
     * @param array<string, mixed> $data
     * @return array{user: User, token: string, token_type: string}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'id' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone_number' => $data['phone_number'],
                'nim' => $data['nim'] ?? null,
                'faculty' => $data['faculty'] ?? null,
                'study_program' => $data['study_program'] ?? null,
            ]);

            $roleName = $data['role'] ?? RoleEnum::STUDENT->value;
            $role = Role::where('name', $roleName)->firstOrFail();
            $user->roles()->attach($role->id);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user->load('roles'),
                'token' => $token,
                'token_type' => 'Bearer',
            ];
        });
    }

    /**
     * Authenticate user credentials and issue an API token.
     *
     * @return array{user: User, token: string, token_type: string}
     * @throws ValidationException
     */
    public function login(string $email, string $password): array
    {
        $user = User::with('roles')->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial email atau password yang dimasukkan salah.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Revoke current user's API token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Get user profile with roles.
     */
    public function getProfile(User $user): User
    {
        return $user->load('roles');
    }
}
