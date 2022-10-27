<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class QuoteProcessStatuses extends Enum
{
    const NEW = 'NEW';
    const VALIDATION_FAILED = 'VALIDATION_FAILED';
    const VALIDATED = 'VALIDATED';
    const PROCESSED = 'PROCESSED';
    const EMAIL_SENT = 'EMAIL_SENT';
}
