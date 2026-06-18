<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CorplineLeadTypeEnum extends Enum
{
    const RENEWAL = 'Renewal';
    const NON_RENEWAL = 'Non-Renewal';
    const EXTENDABLE = 'Extendable';
}
