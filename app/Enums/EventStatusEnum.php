<?php

declare(strict_types=1);

namespace App\Enums;

enum EventStatusEnum: string
{
    case DRAFT = 'DRAFT';
    case PENDING_APPROVAL = 'PENDING_APPROVAL';
    case PUBLISHED = 'PUBLISHED';
    case REJECTED = 'REJECTED';
    case COMPLETED = 'COMPLETED';
}
