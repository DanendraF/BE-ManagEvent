<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannelEnum: string
{
    case WHATSAPP = 'WHATSAPP';
    case EMAIL = 'EMAIL';
    case INSTAGRAM = 'INSTAGRAM';
}
