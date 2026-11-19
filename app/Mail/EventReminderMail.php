<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Registration $registration,
        public string $reminderType = 'REMINDER_H1'
    ) {}

    public function envelope(): Envelope
    {
        $eventName = $this->registration->event?->title ?? 'Kegiatan Kampus UII';
        $prefix = $this->reminderType === 'REMINDER_H3' ? '⏰ [H-3 Jam]' : '📅 [H-1]';

        return new Envelope(
            subject: "{$prefix} Pengingat Acara: {$eventName} - UII Event Management",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder',
        );
    }
}
