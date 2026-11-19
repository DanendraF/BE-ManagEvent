<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatusEnum;
use App\Enums\RegistrationStatusEnum;
use App\Jobs\Notifications\SendEventReminderJob;
use App\Models\Event;
use App\Models\NotificationLog;
use App\Models\Registration;
use Illuminate\Console\Command;

class SendEventRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-reminders 
                            {--type=all : Reminder type (all, h1, h3)}
                            {--event= : Optional specific Event UUID to process}
                            {--dry-run : Simulate without dispatching jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch automated reminder notifications (H-1 & H-3 Hours) for upcoming events';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = strtolower((string) $this->option('type'));
        $eventId = $this->option('event');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("🔍 Memeriksa jadwal acara untuk pengiriman pengingat (Mode: {$type})...");

        $dispatchedCount = 0;

        if ($type === 'all' || $type === 'h1') {
            $dispatchedCount += $this->processH1Reminders($eventId, $isDryRun);
        }

        if ($type === 'all' || $type === 'h3') {
            $dispatchedCount += $this->processH3Reminders($eventId, $isDryRun);
        }

        $this->info("✅ Pemindaian selesai. Total pengingat dijadwalkan: {$dispatchedCount}");

        return self::SUCCESS;
    }

    /**
     * Process H-1 reminders (events starting in 23-25 hours, or specific target event).
     */
    protected function processH1Reminders(?string $eventId, bool $isDryRun): int
    {
        $now = now();
        $query = Event::query()->where('status', EventStatusEnum::PUBLISHED);

        if ($eventId) {
            $query->where('id', $eventId);
        } else {
            $query->whereBetween('start_time', [
                $now->copy()->addHours(23),
                $now->copy()->addHours(25),
            ]);
        }

        $events = $query->get();
        $count = 0;

        foreach ($events as $event) {
            $registrations = Registration::query()
                ->where('event_id', $event->id)
                ->where('status', RegistrationStatusEnum::REGISTERED)
                ->get();

            foreach ($registrations as $reg) {
                // Idempotency check: Skip if already sent H-1
                $alreadySent = NotificationLog::query()
                    ->where('registration_id', $reg->id)
                    ->where('delivery_status', 'SENT')
                    ->where('response_payload', 'LIKE', '%"type":"REMINDER_H1"%')
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                if (! $isDryRun) {
                    SendEventReminderJob::dispatch($reg->id, 'REMINDER_H1');
                }

                $count++;
            }
        }

        $this->line("   📅 Pengingat H-1: {$count} peserta antrean job.");

        return $count;
    }

    /**
     * Process H-3 reminders (events starting in 2.5 - 3.5 hours, or specific target event).
     */
    protected function processH3Reminders(?string $eventId, bool $isDryRun): int
    {
        $now = now();
        $query = Event::query()->where('status', EventStatusEnum::PUBLISHED);

        if ($eventId) {
            $query->where('id', $eventId);
        } else {
            $query->whereBetween('start_time', [
                $now->copy()->addHours(2)->addMinutes(30),
                $now->copy()->addHours(3)->addMinutes(30),
            ]);
        }

        $events = $query->get();
        $count = 0;

        foreach ($events as $event) {
            $registrations = Registration::query()
                ->where('event_id', $event->id)
                ->where('status', RegistrationStatusEnum::REGISTERED)
                ->get();

            foreach ($registrations as $reg) {
                // Idempotency check: Skip if already sent H-3
                $alreadySent = NotificationLog::query()
                    ->where('registration_id', $reg->id)
                    ->where('delivery_status', 'SENT')
                    ->where('response_payload', 'LIKE', '%"type":"REMINDER_H3"%')
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                if (! $isDryRun) {
                    SendEventReminderJob::dispatch($reg->id, 'REMINDER_H3');
                }

                $count++;
            }
        }

        $this->line("   ⏰ Pengingat H-3 Jam: {$count} peserta antrean job.");

        return $count;
    }
}
