<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityTypeEnum: string
{
    case PARTICIPANT_EVENT = 'PARTICIPANT_EVENT';
    case COMMITTEE_RECRUITMENT = 'COMMITTEE_RECRUITMENT';
}
