<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\ActivityTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isOrganizer() || $this->user()?->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:event_categories,id'],
            'description' => ['nullable', 'string'],
            'activity_type' => ['required', 'string', Rule::in([
                ActivityTypeEnum::PARTICIPANT_EVENT->value,
                ActivityTypeEnum::COMMITTEE_RECRUITMENT->value,
            ])],
            'poster_url' => ['required', 'string', 'max:255'],
            'quota_total' => ['required', 'integer', 'min:1'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'location' => ['required', 'string', 'max:150'],
            'auto_post_ig' => ['nullable', 'boolean'],

            // Divisions validation (required if Oprec)
            'divisions' => ['required_if:activity_type,' . ActivityTypeEnum::COMMITTEE_RECRUITMENT->value, 'array'],
            'divisions.*.name' => ['required_with:divisions', 'string', 'max:60'],
            'divisions.*.quota' => ['required_with:divisions', 'integer', 'min:1'],
            'divisions.*.description' => ['nullable', 'string'],
        ];
    }

    /**
     * Custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul kegiatan wajib diisi.',
            'category_id.required' => 'Kategori kegiatan wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'activity_type.required' => 'Tipe kegiatan (Peserta atau Oprec) wajib ditentukan.',
            'poster_url.required' => 'URL poster kegiatan wajib dilampirkan.',
            'quota_total.required' => 'Total kuota kegiatan wajib ditentukan.',
            'quota_total.min' => 'Kuota minimal adalah 1 orang.',
            'start_time.required' => 'Waktu mulai kegiatan wajib ditentukan.',
            'start_time.after' => 'Waktu mulai kegiatan harus di masa mendatang.',
            'end_time.required' => 'Waktu selesai kegiatan wajib ditentukan.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'location.required' => 'Lokasi kegiatan atau tautan virtual wajib diisi.',
            'divisions.required_if' => 'Kegiatan bertipe Open Recruitment wajib menyertakan minimal 1 pilihan divisi.',
        ];
    }
}
