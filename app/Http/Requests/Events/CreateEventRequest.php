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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Support 'PARTICIPANT', 'SEMINAR', 'WORKSHOP', 'LOMBA', etc. as alias for 'PARTICIPANT_EVENT'
        if ($this->activity_type === 'PARTICIPANT' || ($this->activity_type && $this->activity_type !== ActivityTypeEnum::COMMITTEE_RECRUITMENT->value)) {
            $this->merge(['activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT->value]);
        }

        // Automatic category mapping if string name/code was sent
        if (!$this->has('category_id') || !is_numeric($this->category_id)) {
            $catMap = [
                'SEMINAR' => 1,
                'WORKSHOP' => 2,
                'LOMBA' => 3,
                'KONSER' => 4,
                'KONSER & FESTIVAL' => 4,
                'COMMITTEE_RECRUITMENT' => 5,
                'OPEN RECRUITMENT' => 5,
                'OPREC' => 5,
            ];
            $catKey = strtoupper(trim((string) ($this->category ?? $this->activity_type ?? 'SEMINAR')));
            $this->merge(['category_id' => $catMap[$catKey] ?? 1]);
        } else {
            $this->merge(['category_id' => (int) $this->category_id]);
        }

        // Location fallback from venue_name
        if (!$this->has('location') && $this->has('venue_name')) {
            $this->merge(['location' => (string) $this->venue_name]);
        }

        // Poster URL fallback
        if (!$this->has('poster_url') || empty($this->poster_url)) {
            $this->merge(['poster_url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=80']);
        }

        if ($this->has('quota_total') && is_numeric($this->quota_total)) {
            $this->merge(['quota_total' => (int) $this->quota_total]);
        }

        // If not committee recruitment, strip empty divisions to avoid required_with issues
        if ($this->activity_type !== ActivityTypeEnum::COMMITTEE_RECRUITMENT->value) {
            $this->request->remove('divisions');
        } elseif ($this->has('divisions') && is_array($this->divisions)) {
            $formattedDivisions = [];
            foreach ($this->divisions as $div) {
                if (is_string($div)) {
                    $formattedDivisions[] = ['name' => $div, 'quota' => 10];
                } elseif (is_array($div) && isset($div['name'])) {
                    $formattedDivisions[] = [
                        'name' => (string) $div['name'],
                        'quota' => (int) ($div['quota'] ?? 10),
                        'description' => (string) ($div['description'] ?? '')
                    ];
                }
            }
            if (!empty($formattedDivisions)) {
                $this->merge(['divisions' => $formattedDivisions]);
            }
        }
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
