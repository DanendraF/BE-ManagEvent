<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ticket = $this['ticket'] ?? $this->resource;
        $certificate = $this['certificate'] ?? null;
        $registration = $ticket->registration;
        $user = $registration?->user;
        $event = $registration?->event;

        return [
            'ticket_code' => $ticket->ticket_code,
            'is_checked_in' => $ticket->is_checked_in,
            'checked_in_at' => $ticket->checked_in_at?->toISOString(),
            'checked_in_by' => [
                'id' => $ticket->checkedInBy?->id,
                'name' => $ticket->checkedInBy?->name,
            ],
            'attendee' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'nim' => $user?->nim,
                'email' => $user?->email,
                'faculty' => $user?->faculty,
                'study_program' => $user?->study_program,
            ],
            'event' => [
                'id' => $event?->id,
                'title' => $event?->title,
                'slug' => $event?->slug,
            ],
            'certificate' => $certificate ? new CertificateResource($certificate) : null,
        ];
    }
}
