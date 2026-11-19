<?php

declare(strict_types=1);

namespace App\Enums;

enum RegistrationStatusEnum: string
{
    case REGISTERED = 'REGISTERED';
    case ATTENDED = 'ATTENDED';
    case CANCELLED = 'CANCELLED';
    case WAITING_LIST = 'WAITING_LIST';
}
