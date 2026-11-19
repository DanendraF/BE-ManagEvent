<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Certificate
 */
class CertificateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $registration = $this->registration;
        $event = $registration?->event;
        $user = $registration?->user;

        return [
            'id' => $this->id,
            'certificate_number' => $this->certificate_number,
            'verify_hash' => $this->verify_hash,
            'verify_url' => url("/api/v1/certificates/verify/{$this->verify_hash}"),
            'issued_at' => $this->issued_at->toISOString(),
            'event' => [
                'id' => $event?->id,
                'title' => $event?->title,
                'slug' => $event?->slug,
                'start_time' => $event?->start_time?->toISOString(),
                'end_time' => $event?->end_time?->toISOString(),
                'category' => $event?->category?->name,
            ],
            'recipient' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'nim' => $user?->nim,
                'faculty' => $user?->faculty,
                'study_program' => $user?->study_program,
            ],
            'status' => 'VALID',
        ];
    }
}
