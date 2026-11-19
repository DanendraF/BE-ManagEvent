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

        // 1. Super Admin (Direktorat Kemahasiswaan / DPK UII)
        $superadmin = User::firstOrCreate(
            ['email' => 'admin.dpk@uii.ac.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Dr. Ir. Budi Hartono (DPK UII)',
                'password' => Hash::make('Password123!'),
                'phone_number' => '6281111111111',
                'faculty' => 'Direktorat Pembinaan Kemahasiswaan',
                'study_program' => 'Pusat Manajemen Kampus',
                'email_verified_at' => now(),
            ]
        );
        $superadmin->roles()->syncWithoutDetaching([$superadminRole->id]);

        // 2. Organizer (Panitia LEM FTI UII)
        $organizer = User::firstOrCreate(
            ['email' => 'lemfti@uii.ac.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Sarah Nabila (LEM FTI UII)',
                'password' => Hash::make('Password123!'),
                'phone_number' => '6282222222222',
                'faculty' => 'Fakultas Teknologi Industri',
                'study_program' => 'Informatika',
                'email_verified_at' => now(),
            ]
        );
        $organizer->roles()->syncWithoutDetaching([$organizerRole->id]);

        // 3. Student Demo 1
        $student1 = User::firstOrCreate(
            ['email' => '21523001@students.uii.ac.id'],
            [
                'id' => (string) Str::uuid(),
                'nim' => '21523001',
                'name' => 'Ahmad Fauzan',
                'password' => Hash::make('Password123!'),
                'phone_number' => '6283333333333',
                'faculty' => 'Fakultas Teknologi Industri',
                'study_program' => 'Informatika',
                'email_verified_at' => now(),
            ]
        );
        $student1->roles()->syncWithoutDetaching([$studentRole->id]);

        // 4. Student Demo 2
        $student2 = User::firstOrCreate(
            ['email' => '21523002@students.uii.ac.id'],
            [
                'id' => (string) Str::uuid(),
                'nim' => '21523002',
                'name' => 'Siti Rahmawati',
                'password' => Hash::make('Password123!'),
                'phone_number' => '6284444444444',
                'faculty' => 'Fakultas Bisnis dan Ekonomika',
                'study_program' => 'Manajemen',
                'email_verified_at' => now(),
            ]
        );
        $student2->roles()->syncWithoutDetaching([$studentRole->id]);
    }
}
