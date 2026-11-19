<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'phone_number' => ['required', 'string', 'max:20'],
            'nim' => ['nullable', 'string', 'max:20', 'unique:users,nim'],
            'faculty' => ['nullable', 'string', 'max:80'],
            'study_program' => ['nullable', 'string', 'max:80'],
            'role' => ['nullable', 'string', Rule::in([RoleEnum::STUDENT->value, RoleEnum::ORGANIZER->value])],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email kampus wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar dalam sistem.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'phone_number.required' => 'Nomor WhatsApp aktif wajib diisi.',
            'nim.unique' => 'NIM sudah terdaftar dalam sistem.',
            'role.in' => 'Peran yang dipilih tidak valid untuk pendaftaran publik.',
        ];
    }
}
