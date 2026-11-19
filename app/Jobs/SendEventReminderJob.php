<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Registration;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEventReminderJob implements ShouldQueue
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
        public string $registrationId,
        public string $reminderType = 'REMINDER_H1'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $registration = Registration::with(['user', 'event.category', 'ticket'])->find($this->registrationId);

        if (! $registration) {
            Log::warning("SendEventReminderJob: Registration {$this->registrationId} not found, skipping.");

            return;
        }

        $result = $notificationService->sendEventReminderNotification($registration, $this->reminderType);

        Log::info("SendEventReminderJob completed for registration {$this->registrationId}", $result);
    }
}
