<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityTypeEnum;
use App\Enums\EventStatusEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\RegistrationStatusEnum;
use App\Jobs\SendEventReminderJob;
use App\Jobs\SendTicketNotificationJob;
use App\Mail\EventReminderMail;
use App\Mail\EventTicketMail;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\NotificationLog;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $organizer;
    protected User $otherOrganizer;
    protected User $superAdmin;
    protected Event $event;
    protected Registration $registration;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $studentRole = Role::where('name', 'STUDENT')->firstOrFail();
        $organizerRole = Role::where('name', 'ORGANIZER')->firstOrFail();
        $superAdminRole = Role::where('name', 'SUPERADMIN')->firstOrFail();

        $this->student = User::factory()->create([
            'email' => 'student.notif@uii.ac.id',
            'phone_number' => '081234567890',
        ]);
        $this->student->roles()->attach($studentRole->id);

        $this->organizer = User::factory()->create([
            'email' => 'organizer.notif@uii.ac.id',
            'phone_number' => '081234567891',
        ]);
        $this->organizer->roles()->attach($organizerRole->id);

        $this->otherOrganizer = User::factory()->create([
            'email' => 'other.organizer@uii.ac.id',
            'phone_number' => '081234567892',
        ]);
        $this->otherOrganizer->roles()->attach($organizerRole->id);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin.notif@uii.ac.id',
            'phone_number' => '081234567899',
        ]);
        $this->superAdmin->roles()->attach($superAdminRole->id);

        $category = EventCategory::first();

        $this->event = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $category->id,
            'title' => 'Workshop Notifikasi & AI Kampus UII',
            'slug' => 'workshop-notifikasi-ai-kampus-uii',
            'description' => 'Pelatihan otomatisasi notifikasi dan AI.',
            'poster_url' => 'https://storage.uii.ac.id/posters/notif.jpg',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'start_time' => now()->addHours(24),
            'end_time' => now()->addHours(27),
            'location' => 'Auditorium FTI UII',
            'quota_total' => 50,
            'quota_remaining' => 49,
            'status' => EventStatusEnum::PUBLISHED,
        ]);

        $this->registration = Registration::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->student->id,
            'event_id' => $this->event->id,
            'registration_type' => 'PARTICIPANT',
            'status' => RegistrationStatusEnum::REGISTERED,
            'registered_at' => now(),
        ]);

        $ticketCode = 'TKT-2026-TESTNOTIF';
        $this->ticket = Ticket::create([
            'id' => (string) Str::uuid(),
            'registration_id' => $this->registration->id,
            'ticket_code' => $ticketCode,
            'qr_payload' => base64_encode(json_encode(['code' => $ticketCode])),
            'is_checked_in' => false,
        ]);
    }

    public function test_it_dispatches_ticket_job_on_student_registration(): void
    {
        Queue::fake();

        // Create new published event with available quota
        $category = EventCategory::first();
        $targetEvent = Event::create([
            'id' => (string) Str::uuid(),
            'organizer_id' => $this->organizer->id,
            'category_id' => $category->id,
            'title' => 'Webinar Masa Depan Karir BSI UII',
            'slug' => 'webinar-masa-depan-karir-bsi-uii',
            'description' => 'Webinar karir untuk mahasiswa.',
            'poster_url' => 'https://storage.uii.ac.id/posters/career.jpg',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'start_time' => now()->addDays(5),
            'end_time' => now()->addDays(5)->addHours(2),
            'location' => 'Zoom Meeting',
            'quota_total' => 100,
            'quota_remaining' => 100,
            'status' => EventStatusEnum::PUBLISHED,
        ]);

        $response = $this->actingAs($this->student, 'sanctum')
            ->postJson("/api/v1/events/{$targetEvent->id}/register");

        $response->assertStatus(201);
        Queue::assertPushed(SendTicketNotificationJob::class);
    }

    public function test_it_delivers_ticket_via_whatsapp_when_gateway_succeeds(): void
    {
        Mail::fake();
        Config::set('services.whatsapp.token', 'test_secret_token');
        Config::set('services.whatsapp.endpoint', 'https://api.fonnte.com/send');

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true, 'target' => '081234567890'], 200),
        ]);

        $service = app(NotificationService::class);
        $result = $service->sendTicketIssuedNotification($this->registration);

        $this->assertSame('SENT', $result['status']);
        $this->assertSame('WHATSAPP', $result['channel']);
        $this->assertFalse($result['fallback']);

        $this->assertDatabaseHas('notification_logs', [
            'registration_id' => $this->registration->id,
            'channel' => NotificationChannelEnum::WHATSAPP->value,
            'delivery_status' => 'SENT',
        ]);

        Mail::assertNothingSent();
    }

    public function test_it_falls_back_to_smtp_email_when_whatsapp_gateway_fails(): void
    {
        Mail::fake();
        Config::set('services.whatsapp.token', 'test_secret_token');
        Config::set('services.whatsapp.endpoint', 'https://api.fonnte.com/send');

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['error' => 'Gateway Internal Timeout'], 500),
        ]);

        $service = app(NotificationService::class);
        $result = $service->sendTicketIssuedNotification($this->registration);

        $this->assertSame('SENT', $result['status']);
        $this->assertSame('EMAIL', $result['channel']);
        $this->assertTrue($result['fallback']);

        // Check WA failed log
        $this->assertDatabaseHas('notification_logs', [
            'registration_id' => $this->registration->id,
            'channel' => NotificationChannelEnum::WHATSAPP->value,
            'delivery_status' => 'FAILED',
        ]);

        // Check Email sent log
        $this->assertDatabaseHas('notification_logs', [
            'registration_id' => $this->registration->id,
            'channel' => NotificationChannelEnum::EMAIL->value,
            'delivery_status' => 'SENT',
        ]);

        Mail::assertSent(EventTicketMail::class, function (EventTicketMail $mail) {
            return $mail->hasTo('student.notif@uii.ac.id');
        });
    }

    public function test_it_falls_back_to_email_when_whatsapp_token_is_missing(): void
    {
        Mail::fake();
        Config::set('services.whatsapp.token', '');

        $service = app(NotificationService::class);
        $result = $service->sendTicketIssuedNotification($this->registration);

        $this->assertSame('SENT', $result['status']);
        $this->assertSame('EMAIL', $result['channel']);
        $this->assertTrue($result['fallback']);

        Mail::assertSent(EventTicketMail::class);
    }

    public function test_it_ensures_idempotency_to_prevent_duplicate_ticket_notifications(): void
    {
        Mail::fake();
        Config::set('services.whatsapp.token', '');

        $service = app(NotificationService::class);

        // First attempt succeeds via fallback email
        $first = $service->sendTicketIssuedNotification($this->registration);
        $this->assertSame('SENT', $first['status']);

        // Second attempt must be flagged as ALREADY_SENT
        $second = $service->sendTicketIssuedNotification($this->registration);
        $this->assertSame('ALREADY_SENT', $second['status']);

        Mail::assertSentCount(1);
    }

    public function test_it_sends_event_reminder_and_falls_back_to_email(): void
    {
        Mail::fake();
        Config::set('services.whatsapp.token', '');

        $service = app(NotificationService::class);
        $resultH1 = $service->sendEventReminderNotification($this->registration, 'REMINDER_H1');

        $this->assertSame('SENT', $resultH1['status']);
        $this->assertSame('EMAIL', $resultH1['channel']);

        Mail::assertSent(EventReminderMail::class, function (EventReminderMail $mail) {
            return $mail->hasTo('student.notif@uii.ac.id') && $mail->reminderType === 'REMINDER_H1';
        });

        // Remind H-3 Jam can also be sent separately
        $resultH3 = $service->sendEventReminderNotification($this->registration, 'REMINDER_H3');
        $this->assertSame('SENT', $resultH3['status']);
        Mail::assertSentCount(2);
    }

    public function test_scheduled_command_dispatches_reminders(): void
    {
        Queue::fake();

        // The event starts in 24 hours, so it qualifies for H-1
        $this->artisan('events:send-reminders --type=h1')
            ->assertSuccessful();

        Queue::assertPushed(SendEventReminderJob::class, function (SendEventReminderJob $job) {
            return $job->registrationId === $this->registration->id && $job->reminderType === 'REMINDER_H1';
        });
    }

    public function test_organizer_can_view_event_notification_logs(): void
    {
        // Create sample notification log
        NotificationLog::create([
            'event_id' => $this->event->id,
            'registration_id' => $this->registration->id,
            'channel' => NotificationChannelEnum::EMAIL,
            'recipient' => $this->student->email,
            'delivery_status' => 'SENT',
            'response_payload' => json_encode(['type' => 'TICKET_ISSUED']),
        ]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->getJson("/api/v1/organizer/events/{$this->event->id}/notifications");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'summary' => [
                        'total_notifications',
                        'sent_count',
                        'failed_count',
                        'whatsapp_count',
                        'email_count',
                    ],
                    'notifications',
                    'pagination',
                ],
            ]);
    }

    public function test_organizer_cannot_view_notification_logs_of_other_organizers_event(): void
    {
        $response = $this->actingAs($this->otherOrganizer, 'sanctum')
            ->getJson("/api/v1/organizer/events/{$this->event->id}/notifications");

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'ERR_FORBIDDEN');
    }

    public function test_organizer_can_broadcast_manual_reminders(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson("/api/v1/organizer/events/{$this->event->id}/reminders", [
                'reminder_type' => 'REMINDER_H1',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.dispatched_count', 1);

        Queue::assertPushed(SendEventReminderJob::class);
    }
}
