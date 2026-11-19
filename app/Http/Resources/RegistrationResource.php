<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Registration
 */
class RegistrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registration_type' => $this->registration_type,
            'division_pref_1' => $this->division_pref_1,
            'division_pref_2' => $this->division_pref_2,
            'has_cv' => ! empty($this->cv_file_url),
            'cv_file_url' => $this->cv_file_url,
            'selection_status' => $this->selection_status?->value,
            'status' => $this->status->value,
            'registered_at' => $this->registered_at->toISOString(),
            'custom_answers' => $this->custom_answers,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'nim' => $this->user?->nim,
                'email' => $this->user?->email,
                'phone_number' => $this->user?->phone_number,
                'faculty' => $this->user?->faculty,
                'study_program' => $this->user?->study_program,
            ],
            'event' => $this->whenLoaded('event', function () {
                return [
                    'id' => $this->event->id,
                    'title' => $this->event->title,
                    'slug' => $this->event->slug,
                    'activity_type' => $this->event->activity_type->value,
                    'status' => $this->event->status->value,
                    'start_time' => $this->event->start_time->toISOString(),
                    'end_time' => $this->event->end_time->toISOString(),
                ];
            }),
            'ticket' => new TicketResource($this->whenLoaded('ticket')),
        ];
    }
}
