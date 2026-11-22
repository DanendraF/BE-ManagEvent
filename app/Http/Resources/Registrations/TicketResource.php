<?php

declare(strict_types=1);

namespace App\Http\Resources\Registrations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Ticket
 */
class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $registration = $this->relationLoaded('registration') ? $this->registration : null;
        $event = $registration?->event;
        $user = $registration?->user;

        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'qr_payload' => $this->qr_payload,
            'is_checked_in' => (bool) $this->is_checked_in,
            'checked_in_at' => $this->checked_in_at?->toISOString(),
            'event' => [
                'id' => $event?->id,
                'title' => $event?->title,
                'slug' => $event?->slug,
                'start_time' => $event?->start_time?->toISOString(),
                'end_time' => $event?->end_time?->toISOString(),
                'location' => $event?->location,
                'poster_url' => $event?->poster_url,
                'category' => $event?->category?->name,
            ],
            'participant' => [
                'name' => $user?->name,
                'nim' => $user?->nim,
                'email' => $user?->email,
                'phone_number' => $user?->phone_number,
                'faculty' => $user?->faculty,
                'study_program' => $user?->study_program,
            ],
            'registration_status' => $registration?->status?->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
