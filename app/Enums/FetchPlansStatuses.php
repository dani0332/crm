<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class FetchPlansStatuses extends Enum
{
    public const PENDING = 'Pending';
    public const FETCHED = 'Fetched';
    public const OUTDATED = 'Outdated';
}
