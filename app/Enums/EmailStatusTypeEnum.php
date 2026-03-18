<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailStatusTypeEnum: string
{
    case Email = 'email';

    case WhatsApp = 'whatsApp';
}
