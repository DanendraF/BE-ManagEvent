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
        $hmif = User::where('email', 'hmif@uii.ac.id')->first() ?? $organizer;
        $seminarCat = EventCategory::where('slug', 'seminar')->firstOrFail();
        $workshopCat = EventCategory::where('slug', 'workshop')->firstOrFail();
        $lombaCat = EventCategory::where('slug', 'lomba')->firstOrFail();
        $konserCat = EventCategory::where('slug', 'konser-festival')->firstOrFail();
        $oprecCat = EventCategory::where('slug', 'open-recruitment')->firstOrFail();

        // Helper function for creating/updating events safely
        $seedEvent = function (string $slug, array $attributes) {
            $event = Event::firstOrNew(['slug' => $slug]);
            if (! $event->exists) {
                $event->id = (string) Str::uuid();
            }
            $event->fill($attributes)->save();
            return $event;
        };

        // 1. Seminar Nasional AI 2026 (Published)
        $seedEvent('seminar-nasional-ai-2026', [
            'organizer_id' => $organizer->id,
            'category_id' => $seminarCat->id,
            'title' => 'Seminar Nasional Artificial Intelligence (AI) & Karir Teknologi 2026',
            'description' => "Membahas akselerasi implementasi Generative Artificial Intelligence & Machine Learning dalam transformasi industri Indonesia bersama pakar Google Cloud dan alumni informatika UII.\n\nFasilitas Peserta:\n• E-Sertifikat Resmi ber-SKP Kemahasiswaan UII\n• Seminar Kit Eksklusif & Konsumsi Siang\n• Akses Rekaman Materi & Source Code Lab",
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1080&q=80',
            'quota_total' => 250,
            'quota_remaining' => 42,
            'start_time' => now()->addDays(5)->setTime(8, 30),
            'end_time' => now()->addDays(5)->setTime(12, 0),
            'location' => 'Auditorium Prof. Dr. Sardjito, Kampus Terpadu UII',
            'status' => EventStatusEnum::PUBLISHED,
            'auto_post_ig' => true,
        ]);

        // 2. Workshop Fullstack Laravel (Published)
        $seedEvent('workshop-laravel-api-2026', [
            'organizer_id' => $hmif->id,
            'category_id' => $workshopCat->id,
            'title' => 'Workshop Fullstack Laravel & Modern API Engineering',
            'description' => "Praktik langsung (*hands-on coding*) merancang REST API arsitektur enterprise dengan standar ISO, Laravel Sanctum, dan kontrol konkurensi PostgreSQL (Pessimistic Locking).\n\nPersyaratan Peserta:\n• Membawa laptop dengan PHP 8.2+ dan Composer terpasang\n• Memiliki pemahaman dasar OOP PHP",
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1080&q=80',
            'quota_total' => 60,
            'quota_remaining' => 18,
            'start_time' => now()->addDays(8)->setTime(13, 0),
            'end_time' => now()->addDays(8)->setTime(17, 0),
            'location' => 'Lab Komputer Rekayasa Perangkat Lunak Gedung KH. Mas Mansur FTI UII',
            'status' => EventStatusEnum::PUBLISHED,
            'auto_post_ig' => true,
        ]);

        // 3. Oprec Panitia Ospek Kampus (Published)
        $oprecEvent = $seedEvent('oprec-panitia-ospek-2026', [
            'organizer_id' => $organizer->id,
            'category_id' => $oprecCat->id,
            'title' => 'Open Recruitment Panitia Ospek Kampus & Welcoming Day 2026',
            'description' => "Bergabunglah dalam kepanitiaan terbesar universitas untuk menyambut mahasiswa baru UII Angkatan 2026! Mari berkontribusi nyata, asah jiwa kepemimpinan, dan perluas jejaring ormawa lintas fakultas.\n\nTahapan Seleksi:\n1. Seleksi Berkas Administrasi & Portofolio CV\n2. Wawancara Bidang Divisi\n3. Pengumuman Kelulusan & First Gathering",
            'activity_type' => ActivityTypeEnum::COMMITTEE_RECRUITMENT,
            'poster_url' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1080&q=80',
            'quota_total' => 120,
            'quota_remaining' => 85,
            'start_time' => now()->addDays(12)->setTime(8, 0),
            'end_time' => now()->addDays(12)->setTime(16, 0),
            'location' => 'Student Center Kampus Terpadu UII',
            'status' => EventStatusEnum::PUBLISHED,
            'auto_post_ig' => true,
        ]);

        // Add 4 divisions for Oprec Event (matching tests)
        $divisions = [
            ['name' => 'Divisi Acara & Protokoler', 'quota' => 30, 'description' => 'Konsep rundown, pengarah panggung, kurasi pemateri, dan teknis acara'],
            ['name' => 'Divisi Humas & Sponsorship', 'quota' => 25, 'description' => 'Kemitraan eksternal sponsor, relasi media partner, dan komunikasi ormawa'],
            ['name' => 'Divisi Media Kreatif & PDD', 'quota' => 20, 'description' => 'Desain visual, motion graphic, video teaser, dan dokumentasi live'],
            ['name' => 'Divisi Logistik & Keamanan', 'quota' => 45, 'description' => 'Tata ruang panggung, perlengkapan aula, mitigasi keamanan, dan ketertiban'],
        ];

        EventDivision::where('event_id', $oprecEvent->id)->delete();
        foreach ($divisions as $div) {
            EventDivision::create([
                'event_id' => $oprecEvent->id,
                'name' => $div['name'],
                'quota' => $div['quota'],
                'description' => $div['description'],
            ]);
        }

        // 4. Proposal Menunggu Review DPM (Pending Approval - Ready for DPM Testing)
        $seedEvent('lkmm-tm-kepemimpinan-uii-2026', [
            'organizer_id' => $organizer->id,
            'category_id' => $workshopCat->id,
            'title' => 'Latihan Keterampilan Manajemen Mahasiswa (LKMM-TM) UII 2026',
            'description' => "Proposal program kerja pelatihan kepemimpinan strategis, analisis sosial, diplomasi persidangan, dan manajemen konflik bagi calon fungsionaris lembaga kemahasiswaan se-UII.",
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=1080&q=80',
            'quota_total' => 80,
            'quota_remaining' => 80,
            'start_time' => now()->addDays(30)->setTime(8, 0),
            'end_time' => now()->addDays(31)->setTime(17, 0),
            'location' => 'Gedung Kuliah Umum (GKU) Dr. Soekiman Wirjosandjojo Lt. 2',
            'status' => EventStatusEnum::PENDING_APPROVAL,
            'auto_post_ig' => true,
        ]);

        // 5. Draft Event (Should NOT appear in public catalog)
        $seedEvent('draf-konser-internal-2026', [
            'organizer_id' => $organizer->id,
            'category_id' => $seminarCat->id,
            'title' => 'Draf Proposal Bedah Buku Nasional & Diskusi Keislaman UII 2026',
            'description' => 'Proposal kegiatan bedah buku masih dalam penyusunan anggaran pengurus ormawa.',
            'activity_type' => ActivityTypeEnum::PARTICIPANT_EVENT,
            'poster_url' => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=1080&q=80',
            'quota_total' => 200,
            'quota_remaining' => 200,
            'start_time' => now()->addDays(40)->setTime(9, 0),
            'end_time' => now()->addDays(40)->setTime(13, 0),
            'location' => 'Ruang Teatrikal Masjid Ulil Albab Kampus Terpadu UII',
            'status' => EventStatusEnum::DRAFT,
            'auto_post_ig' => false,
        ]);
    }
}
