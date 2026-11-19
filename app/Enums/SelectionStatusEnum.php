<?php

declare(strict_types=1);

namespace App\Enums;

enum SelectionStatusEnum: string
{
    case SUBMITTED = 'SUBMITTED';
    case INTERVIEW_SCHEDULED = 'INTERVIEW_SCHEDULED';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
}
