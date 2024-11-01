<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LogMessagePrefixEnum extends Enum
{
    const PARENT_PAYMENT_LOG_PREFIX = 'Master payment code: ';
    const CHILD_PAYMENT_LOG_PREFIX = 'Child payment code: ';
}
