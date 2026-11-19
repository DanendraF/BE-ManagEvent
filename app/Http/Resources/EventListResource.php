<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Event
 */
class EventListResource extends JsonResource
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
            'category' => $this->category?->name,
            'activity_type' => $this->activity_type->value,
            'organizer_name' => $this->organizer?->name,
            'poster_url' => $this->poster_url,
            'start_time' => $this->start_time->toISOString(),
            'end_time' => $this->end_time->toISOString(),
            'location' => $this->location,
            'quota_total' => $this->quota_total,
            'quota_remaining' => $this->quota_remaining,
            'is_quota_full' => $this->quota_remaining <= 0,
        ];
    }
}
