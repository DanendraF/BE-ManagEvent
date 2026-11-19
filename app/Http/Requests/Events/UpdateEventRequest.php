<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\ActivityTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
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
        if ($this->activity_type === 'PARTICIPANT') {
            $this->merge(['activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT->value]);
        }

        if ($this->has('category_id') && is_numeric($this->category_id)) {
            $this->merge(['category_id' => (int) $this->category_id]);
        }

        if ($this->has('quota_total') && is_numeric($this->quota_total)) {
            $this->merge(['quota_total' => (int) $this->quota_total]);
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
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:event_categories,id'],
            'description' => ['nullable', 'string'],
            'activity_type' => ['sometimes', 'required', 'string', Rule::in([
                ActivityTypeEnum::PARTICIPANT_EVENT->value,
                ActivityTypeEnum::COMMITTEE_RECRUITMENT->value,
            ])],
            'poster_url' => ['sometimes', 'required', 'string', 'max:255'],
            'quota_total' => ['sometimes', 'required', 'integer', 'min:1'],
            'start_time' => ['sometimes', 'required', 'date', 'after:now'],
            'end_time' => ['sometimes', 'required', 'date', 'after:start_time'],
            'location' => ['sometimes', 'required', 'string', 'max:150'],
            'auto_post_ig' => ['nullable', 'boolean'],

            'divisions' => ['nullable', 'array'],
            'divisions.*.id' => ['nullable', 'integer'],
            'divisions.*.name' => ['required_with:divisions', 'string', 'max:60'],
            'divisions.*.quota' => ['required_with:divisions', 'integer', 'min:1'],
            'divisions.*.description' => ['nullable', 'string'],
        ];
    }
}
