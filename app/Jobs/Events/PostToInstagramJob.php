<?php

declare(strict_types=1);

namespace App\Jobs\Events;

use App\Models\Event;
use App\Services\Events\InstagramPublishingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PostToInstagramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $eventId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(InstagramPublishingService $instagramService): void
    {
        $event = Event::with(['organizer', 'category'])->find($this->eventId);

        if (! $event) {
            Log::warning("PostToInstagramJob: Event {$this->eventId} not found, skipping.");

            return;
        }

        $result = $instagramService->publishEvent($event);

        Log::info("PostToInstagramJob completed for event {$this->eventId}", $result);
    }
}
