<?php

declare(strict_types=1);

namespace App\Http\Resources\Notifications;

use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NotificationLog
 */
class NotificationLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = $this->response_payload;
        $decoded = null;
        if ($payload) {
            $decoded = json_decode($payload, true) ?? $payload;
        }

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'registration_id' => $this->registration_id,
            'participant_name' => $this->registration?->user?->name,
            'channel' => is_string($this->channel) ? $this->channel : $this->channel->value,
            'recipient' => $this->recipient,
            'delivery_status' => $this->delivery_status,
            'payload' => $decoded,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
