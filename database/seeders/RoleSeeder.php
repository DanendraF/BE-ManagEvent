<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id' => 1,
                'name' => RoleEnum::STUDENT->value,
                'label' => RoleEnum::STUDENT->label(),
            ],
            [
                'id' => 2,
                'name' => RoleEnum::ORGANIZER->value,
                'label' => RoleEnum::ORGANIZER->label(),
            ],
            [
                'id' => 3,
                'name' => RoleEnum::SUPERADMIN->value,
                'label' => RoleEnum::SUPERADMIN->label(),
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                [
                    'name' => $role['name'],
                    'label' => $role['label'],
                ]
            );
        }
    }
}
