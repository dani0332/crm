<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LifeRiderEnum extends Enum
{
    const CRITICAL_ILLNESS = 'CI';
    const PERMANENT_AND_TOTAL_DISABILITY = 'PTD';
    const WAIVER_OF_PREMIUM = 'WOP';
}
