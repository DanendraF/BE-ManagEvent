<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleEnum: string
{
    case STUDENT = 'STUDENT';
    case ORGANIZER = 'ORGANIZER';
    case SUPERADMIN = 'SUPERADMIN';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Mahasiswa UII',
            self::ORGANIZER => 'Panitia LEM / UKM',
            self::SUPERADMIN => 'Dewan Perwakilan Mahasiswa (DPM)',
        };
    }
}
