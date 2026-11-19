<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannelEnum;
use App\Models\Event;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstagramPublishingService
{
    /**
     * Publish an event poster to the official campus Instagram account via Meta Graph API.
     *
     * @return array{status: string, post_id?: string, error?: string}
     */
    public function publishEvent(Event $event): array
    {
        $event->loadMissing(['organizer', 'category']);

        // 1. Idempotency Check: Prevent duplicate posts for the same event
        $alreadyPosted = NotificationLog::query()
            ->where('event_id', $event->id)
            ->where('channel', NotificationChannelEnum::INSTAGRAM)
            ->where('delivery_status', 'SENT')
            ->first();

        if ($alreadyPosted) {
            $payload = json_decode((string) $alreadyPosted->response_payload, true) ?? [];

            return [
                'status' => 'ALREADY_POSTED',
                'post_id' => $payload['post_id'] ?? null,
            ];
        }

        $accountId = (string) config('services.instagram.account_id', '');
        $token = (string) config('services.instagram.access_token', '');
        $version = (string) config('services.instagram.graph_version', 'v19.0');
        $baseUrl = rtrim((string) config('services.instagram.base_url', 'https://graph.facebook.com'), '/');
        $timeout = (int) config('services.instagram.timeout', 15);

        $imageUrl = $event->poster_url;
        $caption = $this->formatInstagramCaption($event);

        if (empty($accountId) || empty($token)) {
            NotificationLog::create([
                'event_id' => $event->id,
                'registration_id' => null,
                'channel' => NotificationChannelEnum::INSTAGRAM,
                'recipient' => $accountId ?: 'INSTAGRAM_CAMPUS',
                'delivery_status' => 'FAILED',
                'response_payload' => json_encode([
                    'error' => 'INSTAGRAM_NOT_CONFIGURED: Account ID or Access Token is missing in environment.',
                ]),
            ]);

            return [
                'status' => 'FAILED',
                'error' => 'Instagram credentials not configured in environment.',
            ];
        }

        try {
            // Step 1: Create Media Container on Meta Graph API
            $containerUrl = "{$baseUrl}/{$version}/{$accountId}/media";
            $containerRes = Http::timeout($timeout)
                ->post($containerUrl, [
                    'image_url' => $imageUrl,
                    'caption' => $caption,
                    'access_token' => $token,
                ]);

            if (! $containerRes->successful()) {
                $errBody = $containerRes->json() ?? ['raw' => $containerRes->body()];
                NotificationLog::create([
                    'event_id' => $event->id,
                    'registration_id' => null,
                    'channel' => NotificationChannelEnum::INSTAGRAM,
                    'recipient' => $accountId,
                    'delivery_status' => 'FAILED',
                    'response_payload' => json_encode([
                        'step' => 'create_container',
                        'status_code' => $containerRes->status(),
                        'error' => $errBody,
                    ]),
                ]);

                Log::warning("Instagram media container creation failed: {$containerRes->body()}");

                return [
                    'status' => 'FAILED',
                    'error' => 'Meta Graph API failed to create media container.',
                ];
            }

            $creationId = $containerRes->json('id');

            // Step 2: Publish the Media Container
            $publishUrl = "{$baseUrl}/{$version}/{$accountId}/media_publish";
            $publishRes = Http::timeout($timeout)
                ->post($publishUrl, [
                    'creation_id' => $creationId,
                    'access_token' => $token,
                ]);

            if (! $publishRes->successful()) {
                $errBody = $publishRes->json() ?? ['raw' => $publishRes->body()];
                NotificationLog::create([
                    'event_id' => $event->id,
                    'registration_id' => null,
                    'channel' => NotificationChannelEnum::INSTAGRAM,
                    'recipient' => $accountId,
                    'delivery_status' => 'FAILED',
                    'response_payload' => json_encode([
                        'step' => 'media_publish',
                        'creation_id' => $creationId,
                        'status_code' => $publishRes->status(),
                        'error' => $errBody,
                    ]),
                ]);

                Log::warning("Instagram media publish failed: {$publishRes->body()}");

                return [
                    'status' => 'FAILED',
                    'error' => 'Meta Graph API failed to publish media.',
                ];
            }

            $postId = (string) $publishRes->json('id');

            NotificationLog::create([
                'event_id' => $event->id,
                'registration_id' => null,
                'channel' => NotificationChannelEnum::INSTAGRAM,
                'recipient' => $accountId,
                'delivery_status' => 'SENT',
                'response_payload' => json_encode([
                    'post_id' => $postId,
                    'creation_id' => $creationId,
                    'caption' => $caption,
                    'published_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::info("Instagram post published successfully for event {$event->id}, post ID: {$postId}");

            return [
                'status' => 'SENT',
                'post_id' => $postId,
            ];
        } catch (Throwable $e) {
            NotificationLog::create([
                'event_id' => $event->id,
                'registration_id' => null,
                'channel' => NotificationChannelEnum::INSTAGRAM,
                'recipient' => $accountId,
                'delivery_status' => 'FAILED',
                'response_payload' => json_encode([
                    'exception' => $e->getMessage(),
                ]),
            ]);

            Log::error("Instagram auto-post exception: {$e->getMessage()}");

            return [
                'status' => 'FAILED',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format a captivating, informative caption for the Instagram feed post.
     */
    public function formatInstagramCaption(Event $event): string
    {
        $category = $event->category?->name ?? 'Kegiatan Kampus';
        $startTime = $event->start_time?->format('d M Y, H:i') ?? '-';
        $location = $event->location ?? 'Daring / Online';
        $organizer = $event->organizer?->name ?? 'Panitia UII';
        $quota = $event->quota_total ?? 0;

        return "📢 [INFORMASI KEGIATAN KAMPUS UII]\n\n"
            . "✨ {$event->title} ✨\n\n"
            . "Kategori: #{$category}\n"
            . "📅 Waktu Pelaksanaan: {$startTime} WIB\n"
            . "📍 Lokasi / Venue: {$location}\n"
            . "👥 Kuota Tersedia: {$quota} Peserta\n"
            . "🏛️ Penyelenggara: {$organizer}\n\n"
            . "Deskripsi Singkat:\n"
            . "{$event->description}\n\n"
            . "📌 Pendaftaran resmi dan tiket digital ber-QR Code dapat diakses langsung melalui UII Event Management Platform.\n\n"
            . "#UII #UniversitasIslamIndonesia #EventUII #MahasiswaUII #BSIUII #DPMUII #AgendaKampus";
    }
}
