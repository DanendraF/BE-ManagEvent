<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RegistrationStatusEnum;
use App\Enums\SelectionStatusEnum;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RealTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds for realistic user transactions.
     */
    public function run(): void
    {
        $student1 = User::where('email', '21523001@students.uii.ac.id')->first();
        $student2 = User::where('email', '21523002@students.uii.ac.id')->first();
        $organizer = User::where('email', 'lemfti@uii.ac.id')->first();

        if (! $student1 || ! $organizer) {
            return;
        }

        $seminarEvent = Event::where('slug', 'seminar-nasional-ai-2026')->first();
        $workshopEvent = Event::where('slug', 'workshop-laravel-api-2026')->first();
        $oprecEvent = Event::where('slug', 'oprec-panitia-ospek-2026')->first();
        $rawSecret = (string) config('app.key');

        // ========================================================
        // 1. Tiket Aktif (Menunggu Presensi) - Danendra Farrel Adriansyah
        // ========================================================
        if ($seminarEvent) {
            $regSeminar = Registration::firstOrNew([
                'user_id' => $student1->id,
                'event_id' => $seminarEvent->id,
            ]);
            if (! $regSeminar->exists) {
                $regSeminar->id = (string) Str::uuid();
            }
            $regSeminar->fill([
                'registration_type' => 'PARTICIPANT',
                'custom_answers' => [
                    'motivasi' => 'Ingin mendalami penerapan AI dan LLM pada ekosistem industri teknologi modern.',
                    'kebutuhan_khusus' => 'Tidak ada alergi makanan',
                ],
                'status' => RegistrationStatusEnum::REGISTERED,
                'registered_at' => now()->subDays(2),
            ])->save();

            $ticketCode1 = 'TKT-2026-UII9981X';
            $sig1 = hash_hmac('sha256', "{$ticketCode1}|{$student1->id}|{$seminarEvent->id}", $rawSecret);
            $payload1 = base64_encode(json_encode([
                'ticket_code' => $ticketCode1,
                'reg_id' => $regSeminar->id,
                'user_id' => $student1->id,
                'event_id' => $seminarEvent->id,
                'sig' => $sig1,
            ]));

            $ticket1 = Ticket::firstOrNew(['registration_id' => $regSeminar->id]);
            if (! $ticket1->exists) {
                $ticket1->id = (string) Str::uuid();
            }
            $ticket1->fill([
                'ticket_code' => $ticketCode1,
                'qr_payload' => $payload1,
                'is_checked_in' => false,
            ])->save();
        }

        // ========================================================
        // 2. Tiket Sudah Hadir & E-Sertifikat Terbit - Danendra Farrel Adriansyah
        // ========================================================
        if ($workshopEvent) {
            $regWorkshop = Registration::firstOrNew([
                'user_id' => $student1->id,
                'event_id' => $workshopEvent->id,
            ]);
            if (! $regWorkshop->exists) {
                $regWorkshop->id = (string) Str::uuid();
            }
            $regWorkshop->fill([
                'registration_type' => 'PARTICIPANT',
                'custom_answers' => [
                    'motivasi' => 'Mempersiapkan diri untuk sertifikasi backend developer dan magang industri.',
                    'kebutuhan_khusus' => 'Vegetarian',
                ],
                'status' => RegistrationStatusEnum::ATTENDED,
                'registered_at' => now()->subDays(4),
            ])->save();

            $ticketCode2 = 'TKT-2026-LARAVEL1';
            $sig2 = hash_hmac('sha256', "{$ticketCode2}|{$student1->id}|{$workshopEvent->id}", $rawSecret);
            $payload2 = base64_encode(json_encode([
                'ticket_code' => $ticketCode2,
                'reg_id' => $regWorkshop->id,
                'user_id' => $student1->id,
                'event_id' => $workshopEvent->id,
                'sig' => $sig2,
            ]));

            $ticket2 = Ticket::firstOrNew(['registration_id' => $regWorkshop->id]);
            if (! $ticket2->exists) {
                $ticket2->id = (string) Str::uuid();
            }
            $ticket2->fill([
                'ticket_code' => $ticketCode2,
                'qr_payload' => $payload2,
                'is_checked_in' => true,
                'checked_in_at' => now()->subHours(3),
                'checked_in_by' => $organizer->id,
            ])->save();

            // E-Sertifikat Resmi
            $verifyHash = '7e4d29a58b6883bfd12f5a6f23490b6cb908920153835e5b74681615b136894c';
            $cert = Certificate::firstOrNew(['registration_id' => $regWorkshop->id]);
            if (! $cert->exists) {
                $cert->id = (string) Str::uuid();
            }
            $cert->fill([
                'certificate_number' => 'CERT/UII/2026/LARA-0881',
                'verify_hash' => $verifyHash,
                'issued_at' => now()->subHours(3),
            ])->save();
        }

        // ========================================================
        // 3. Lamaran Oprec Panitia Mahasiswa - Danendra Farrel Adriansyah
        // ========================================================
        if ($oprecEvent) {
            $regOprec = Registration::firstOrNew([
                'user_id' => $student1->id,
                'event_id' => $oprecEvent->id,
            ]);
            if (! $regOprec->exists) {
                $regOprec->id = (string) Str::uuid();
            }
            $regOprec->fill([
                'registration_type' => 'COMMITTEE',
                'division_pref_1' => 'Divisi Acara & Protokoler',
                'division_pref_2' => 'Divisi Media Kreatif & PDD',
                'cv_file_url' => 'recruitment_cvs/' . Str::uuid() . '.pdf',
                'selection_status' => SelectionStatusEnum::INTERVIEW_SCHEDULED,
                'status' => RegistrationStatusEnum::REGISTERED,
                'custom_answers' => [
                    'pengalaman' => 'Pernah menjadi staf divisi acara seminar nasional dan aktif di himpunan mahasiswa.',
                    'catatan_panitia' => 'Lolos verifikasi berkas administrasi. Wawancara tatap muka dijadwalkan pada Sabtu, 28 Nov 2026 pukul 10.00 WIB di Ruang Sidang Lembaga Mahasiswa UII Gedung Student Center.',
                ],
                'registered_at' => now()->subDays(1),
            ])->save();
        }

        // ========================================================
        // 4. Pendaftar Kedua (Muhammad Raihan Pratama)
        // ========================================================
        if ($seminarEvent && $student2) {
            $regStudent2 = Registration::firstOrNew([
                'user_id' => $student2->id,
                'event_id' => $seminarEvent->id,
            ]);
            if (! $regStudent2->exists) {
                $regStudent2->id = (string) Str::uuid();
            }
            $regStudent2->fill([
                'registration_type' => 'PARTICIPANT',
                'custom_answers' => [
                    'motivasi' => 'Menambah portofolio kemahasiswaan dan jaringan alumni.',
                ],
                'status' => RegistrationStatusEnum::REGISTERED,
                'registered_at' => now()->subDay(),
            ])->save();

            $ticketCode3 = 'TKT-2026-MR77291A';
            $sig3 = hash_hmac('sha256', "{$ticketCode3}|{$student2->id}|{$seminarEvent->id}", $rawSecret);
            $payload3 = base64_encode(json_encode([
                'ticket_code' => $ticketCode3,
                'reg_id' => $regStudent2->id,
                'user_id' => $student2->id,
                'event_id' => $seminarEvent->id,
                'sig' => $sig3,
            ]));

            $ticket3 = Ticket::firstOrNew(['registration_id' => $regStudent2->id]);
            if (! $ticket3->exists) {
                $ticket3->id = (string) Str::uuid();
            }
            $ticket3->fill([
                'ticket_code' => $ticketCode3,
                'qr_payload' => $payload3,
                'is_checked_in' => false,
            ])->save();
        }
    }
}
