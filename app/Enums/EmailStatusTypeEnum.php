<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailStatusTypeEnum: string
{
    use Enumable;

    case WhatsApp = 'whatsApp';
}
