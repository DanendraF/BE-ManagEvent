<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadminRole = Role::where('name', RoleEnum::SUPERADMIN->value)->firstOrFail();
        $organizerRole = Role::where('name', RoleEnum::ORGANIZER->value)->firstOrFail();
        $studentRole = Role::where('name', RoleEnum::STUDENT->value)->firstOrFail();

        // 1. Super Admin (Dewan Perwakilan Mahasiswa / DPM UII)
        $superadmin = User::firstOrNew(['email' => 'admin.dpm@uii.ac.id']);
        if (! $superadmin->exists) {
            $superadmin->id = (string) Str::uuid();
        }
        $superadmin->fill([
            'name' => 'DPM UII (Dewan Perwakilan Mahasiswa)',
            'password' => Hash::make('Password123!'),
            'phone_number' => '6281111111111',
            'faculty' => 'Dewan Perwakilan Mahasiswa',
            'study_program' => 'Lembaga Legislatif Mahasiswa Universitas',
            'email_verified_at' => now(),
        ])->save();
        $superadmin->roles()->syncWithoutDetaching([$superadminRole->id]);

        // 2. Organizer 1 (LEM FTI UII)
        $organizer1 = User::firstOrNew(['email' => 'lemfti@uii.ac.id']);
        if (! $organizer1->exists) {
            $organizer1->id = (string) Str::uuid();
        }
        $organizer1->fill([
            'name' => 'Sarah Nabila (LEM FTI UII)',
            'password' => Hash::make('Password123!'),
            'phone_number' => '6282222222222',
            'faculty' => 'Fakultas Teknologi Industri',
            'study_program' => 'Informatika',
            'email_verified_at' => now(),
        ])->save();
        $organizer1->roles()->syncWithoutDetaching([$organizerRole->id]);

        // 2b. Organizer 2 (HMIF UII)
        $organizer2 = User::firstOrNew(['email' => 'hmif@uii.ac.id']);
        if (! $organizer2->exists) {
            $organizer2->id = (string) Str::uuid();
        }
        $organizer2->fill([
            'name' => 'HMIF UII (Himpunan Mahasiswa Informatika)',
            'password' => Hash::make('Password123!'),
            'phone_number' => '6282233334444',
            'faculty' => 'Fakultas Teknologi Industri',
            'study_program' => 'Informatika',
            'email_verified_at' => now(),
        ])->save();
        $organizer2->roles()->syncWithoutDetaching([$organizerRole->id]);

        // 3. Student Demo 1 (Danendra Farrel Adriansyah)
        $student1 = User::firstOrNew(['email' => '21523001@students.uii.ac.id']);
        if (! $student1->exists) {
            $student1->id = (string) Str::uuid();
        }
        $student1->fill([
            'nim' => '21523001',
            'name' => 'Danendra Farrel Adriansyah',
            'password' => Hash::make('Password123!'),
            'phone_number' => '6283333333333',
            'faculty' => 'Fakultas Teknologi Industri',
            'study_program' => 'Informatika',
            'email_verified_at' => now(),
        ])->save();
        $student1->roles()->syncWithoutDetaching([$studentRole->id]);

        // 4. Student Demo 2 (Muhammad Raihan Pratama)
        $student2 = User::firstOrNew(['email' => '21523002@students.uii.ac.id']);
        if (! $student2->exists) {
            $student2->id = (string) Str::uuid();
        }
        $student2->fill([
            'nim' => '21523002',
            'name' => 'Muhammad Raihan Pratama',
            'password' => Hash::make('Password123!'),
            'phone_number' => '6284444444444',
            'faculty' => 'Fakultas Teknologi Industri',
            'study_program' => 'Informatika',
            'email_verified_at' => now(),
        ])->save();
        $student2->roles()->syncWithoutDetaching([$studentRole->id]);

        // 5. Student Demo 3 (Anisa Putri Salsabila)
        $student3 = User::firstOrNew(['email' => '22311050@students.uii.ac.id']);
        if (! $student3->exists) {
            $student3->id = (string) Str::uuid();
        }
        $student3->fill([
            'nim' => '22311050',
            'name' => 'Anisa Putri Salsabila',
            'password' => Hash::make('Password123!'),
            'phone_number' => '6285555555555',
            'faculty' => 'Fakultas Bisnis dan Ekonomika',
            'study_program' => 'Manajemen',
            'email_verified_at' => now(),
        ])->save();
        $student3->roles()->syncWithoutDetaching([$studentRole->id]);

        // 6. Student Demo 4 (Muhammad Raynor Athaillah - Frontend Default User)
        $raynor = User::firstOrNew(['email' => '23525055@students.uii.ac.id']);
        if (! $raynor->exists) {
            $raynor->id = (string) Str::uuid();
        }
        $raynor->fill([
            'nim' => '23525055',
            'name' => 'Muhammad Raynor Athaillah',
            'password' => Hash::make('password123'),
            'phone_number' => '081234567890',
            'faculty' => 'Fakultas Teknologi Industri',
            'study_program' => 'Teknik Mesin',
            'email_verified_at' => now(),
        ])->save();
        $raynor->roles()->syncWithoutDetaching([$studentRole->id]);

        // 7. Panitia Demo (panitia@uii.ac.id)
        $panitiaDemo = User::firstOrNew(['email' => 'panitia@uii.ac.id']);
        if (! $panitiaDemo->exists) {
            $panitiaDemo->id = (string) Str::uuid();
        }
        $panitiaDemo->fill([
            'nim' => '20523120',
            'name' => 'Panitia UII (Sarah Nabila)',
            'password' => Hash::make('password123'),
            'phone_number' => '081298765432',
            'faculty' => 'Fakultas Teknologi Industri',
            'study_program' => 'Informatika',
            'email_verified_at' => now(),
        ])->save();
        $panitiaDemo->roles()->syncWithoutDetaching([$organizerRole->id]);

        // 8. Admin DPK Demo (budi.hartono@uii.ac.id)
        $adminDemo = User::firstOrNew(['email' => 'budi.hartono@uii.ac.id']);
        if (! $adminDemo->exists) {
            $adminDemo->id = (string) Str::uuid();
        }
        $adminDemo->fill([
            'nim' => 'NIP.19740512',
            'name' => 'Dr. Ir. Budi Hartono, M.T.',
            'password' => Hash::make('password123'),
            'phone_number' => '081122334455',
            'faculty' => 'Direktorat Pembinaan Kemahasiswaan',
            'study_program' => 'Direktur DPK UII',
            'email_verified_at' => now(),
        ])->save();
        $adminDemo->roles()->syncWithoutDetaching([$superadminRole->id]);
    }
}
