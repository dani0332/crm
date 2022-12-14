<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RenewalProcessStatuses extends Enum
{
    public const NEW = 'NEW';
    public const VALIDATION_FAILED = 'VALIDATION_FAILED';
    public const BAD_DATA = 'BAD_DATA';
    public const VALIDATED = 'VALIDATED';
    public const PROCESSED = 'PROCESSED';
    public const PLANS_FETCHED = 'PLANS_FETCHED';
    public const EMAIL_SENT = 'EMAIL_SENT';
    public const FAILED = 'FAILED';
}
