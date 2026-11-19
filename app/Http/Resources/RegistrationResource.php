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
            'ticket' => new TicketResource($this->whenLoaded('ticket')),
        ];
    }
}
