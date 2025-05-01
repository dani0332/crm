<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class FTCEmailLogEnum extends Enum
{
    public const EMAIL_SENT = 'sent';
    public const CLICKED = 'clicked';
    public const OPENED = 'opened';
}
