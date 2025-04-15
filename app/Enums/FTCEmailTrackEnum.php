<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class FTCEmailTrackEnum extends Enum
{
    public const EMAIL_SENT = 'email_sent';
    public const CLICKED = 'clicked';
    public const OPENED = 'opened';    
}
