<?php

declare(strict_types=1);

namespace App\Enums;

enum GroupMedicalRegionEnum: string
{
    case AUH = 'auh';
    case NON_AUH = 'non-auh';
}
