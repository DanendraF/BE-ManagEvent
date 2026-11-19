<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Event
 */
class EventDetailResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'activity_type' => $this->activity_type->value,
            'poster_url' => $this->poster_url,
            'status' => $this->status->value,
            'start_time' => $this->start_time->toISOString(),
            'end_time' => $this->end_time->toISOString(),
            'location' => $this->location,
            'quota_total' => $this->quota_total,
            'quota_remaining' => $this->quota_remaining,
            'is_quota_full' => $this->quota_remaining <= 0,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'organizer' => [
                'id' => $this->organizer?->id,
                'name' => $this->organizer?->name,
                'email' => $this->organizer?->email,
                'phone_number' => $this->organizer?->phone_number,
                'faculty' => $this->organizer?->faculty,
            ],
            'divisions' => $this->when(
                $this->divisions->isNotEmpty(),
                fn () => $this->divisions->map(fn ($div) => [
                    'id' => $div->id,
                    'name' => $div->name,
                    'description' => $div->description,
                    'quota' => $div->quota,
                ])
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
