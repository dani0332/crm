<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class QuoteSyncStatus extends Enum
{
    const WAITING = 0;
    const INPROGRESS = 1;
    const FAILED = 2;
    const COMPLETED = 3;
}
