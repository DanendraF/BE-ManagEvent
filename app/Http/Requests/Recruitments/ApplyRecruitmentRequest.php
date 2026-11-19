<?php

declare(strict_types=1);

namespace App\Http\Requests\Recruitments;

use Illuminate\Foundation\Http\FormRequest;

class ApplyRecruitmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'division_pref_1' => ['required', 'string', 'max:60'],
            'division_pref_2' => ['nullable', 'string', 'max:60', 'different:division_pref_1'],
            'cv_file' => ['required', 'file', 'mimes:pdf', 'max:5120'], // Max 5MB
            'custom_answers' => ['nullable', 'array'],
        ];
    }

    /**
     * Custom message validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'division_pref_1.required' => 'Pilihan divisi utama (Pilihan 1) wajib diisi.',
            'division_pref_2.different' => 'Pilihan divisi kedua tidak boleh sama dengan pilihan divisi utama.',
            'cv_file.required' => 'Berkas CV/Portofolio wajib diunggah.',
            'cv_file.mimes' => 'Berkas CV harus dalam format dokumen PDF (.pdf).',
            'cv_file.max' => 'Ukuran berkas CV maksimal 5MB.',
        ];
    }
}
