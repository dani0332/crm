<?php

declare(strict_types=1);

namespace App\Enums;

enum AmlAutomationStatus: string
{
    case Queue = 'queue';
    case Processing = 'processing';
    case Complete = 'complete';
    case Failed = 'failed';
}
