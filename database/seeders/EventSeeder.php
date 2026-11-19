<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityTypeEnum;
use App\Enums\EventStatusEnum;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventDivision;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizer = User::where('email', 'lemfti@uii.ac.id')->firstOrFail();
        $seminarCat = EventCategory::where('slug', 'seminar')->firstOrFail();
        $workshopCat = EventCategory::where('slug', 'workshop')->firstOrFail();
        $oprecCat = EventCategory::where('slug', 'open-recruitment')->firstOrFail();

        // 1. Seminar Nasional AI 2026 (Published)
        Event::updateOrCreate(
            ['slug' => 'seminar-nasional-ai-2026'],
            [
                'id' => (string) Str::uuid(),
                'organizer_id' => $organizer->id,
                'category_id' => $seminarCat->id,
                'title' => 'Seminar Nasional AI & Karir Teknologi 2026',
                'description' => 'Membahas tren artificial intelligence masa depan dan kesiapan karir lulusan informatika.',
                'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
                'poster_url' => 'https://storage.uii-event.id/posters/ai-2026.jpg',
                'quota_total' => 250,
                'quota_remaining' => 42,
                'start_time' => now()->addDays(5)->setTime(8, 30),
                'end_time' => now()->addDays(5)->setTime(12, 0),
                'location' => 'Auditorium KH. Abdurrahman Wahid FTI UII',
                'status' => EventStatusEnum::PUBLISHED,
                'auto_post_ig' => true,
            ]
        );

        // 2. Workshop Fullstack Laravel (Published)
        Event::updateOrCreate(
            ['slug' => 'workshop-laravel-api-2026'],
            [
                'id' => (string) Str::uuid(),
                'organizer_id' => $organizer->id,
                'category_id' => $workshopCat->id,
                'title' => 'Workshop Fullstack Laravel & Modern API Engineering',
                'description' => 'Praktik langsung merancang REST API dengan standard ISO, Sanctum, dan PostgreSQL concurrency control.',
                'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
                'poster_url' => 'https://storage.uii-event.id/posters/workshop-laravel.jpg',
                'quota_total' => 50,
                'quota_remaining' => 15,
                'start_time' => now()->addDays(8)->setTime(13, 0),
                'end_time' => now()->addDays(8)->setTime(17, 0),
                'location' => 'Laboratorium Rekayasa Perangkat Lunak Gedung Mas Mansyur FTI UII',
                'status' => EventStatusEnum::PUBLISHED,
                'auto_post_ig' => true,
            ]
        );

        // 3. Oprec Panitia Ospek Kampus (Published)
        $oprecEvent = Event::updateOrCreate(
            ['slug' => 'oprec-panitia-ospek-2026'],
            [
                'id' => (string) Str::uuid(),
                'organizer_id' => $organizer->id,
                'category_id' => $oprecCat->id,
                'title' => 'Open Recruitment Panitia Ospek Kampus & Welcoming Day 2026',
                'description' => 'Bergabunglah dalam kepanitiaan terbesar universitas untuk menyambut mahasiswa baru UII 2026.',
                'activity_type' => ActivityTypeEnum::COMMITTEE_RECRUITMENT,
                'poster_url' => 'https://storage.uii-event.id/posters/oprec-ospek.jpg',
                'quota_total' => 100,
                'quota_remaining' => 100,
                'start_time' => now()->addDays(12)->setTime(8, 0),
                'end_time' => now()->addDays(12)->setTime(16, 0),
                'location' => 'Student Center Kampus Terpadu UII',
                'status' => EventStatusEnum::PUBLISHED,
                'auto_post_ig' => true,
            ]
        );

        // Add divisions for Oprec Event
        $divisions = [
            ['name' => 'Divisi Acara & Konsep', 'quota' => 25, 'description' => 'Rundown, pembicara, dan pengarah panggung'],
            ['name' => 'Divisi Humas & Sponsorship', 'quota' => 20, 'description' => 'Kemitraan eksternal dan publikasi kampus'],
            ['name' => 'Divisi Media Kreatif', 'quota' => 20, 'description' => 'Desain grafis, video teaser, dan dokumentasi'],
            ['name' => 'Divisi Logistik & Keamanan', 'quota' => 35, 'description' => 'Perlengkapan panggung, aula, dan ketertiban'],
        ];

        foreach ($divisions as $div) {
            EventDivision::updateOrCreate(
                ['event_id' => $oprecEvent->id, 'name' => $div['name']],
                ['quota' => $div['quota'], 'description' => $div['description']]
            );
        }

        // 4. Draft Event (Should NOT appear in public catalog)
        Event::updateOrCreate(
            ['slug' => 'draf-konser-internal-2026'],
            [
                'id' => (string) Str::uuid(),
                'organizer_id' => $organizer->id,
                'category_id' => $seminarCat->id,
                'title' => 'Draf Konser Internal Mahasiswa (Belum Disetujui)',
                'description' => 'Acara masih dalam bentuk draf proposal pengurus.',
                'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
                'poster_url' => 'https://storage.uii-event.id/posters/draft.jpg',
                'quota_total' => 500,
                'quota_remaining' => 500,
                'start_time' => now()->addDays(20)->setTime(19, 0),
                'end_time' => now()->addDays(20)->setTime(23, 0),
                'location' => 'Lapangan Parkir GOR Ki Bagoes Hadikoesoemo UII',
                'status' => EventStatusEnum::DRAFT,
                'auto_post_ig' => false,
            ]
        );
    }
}
