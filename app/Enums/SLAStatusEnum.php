<?php

declare(strict_types=1);

namespace App\Enums;

enum SLAStatusEnum: string
{
    use Enumable;

    case ACTIVE = 'active';
    case MET = 'met';
    case BREACHED = 'breached';
    case CANCELED = 'canceled';
    case REASSIGNED = 'reassigned';

    public static function getFinalStatuses(): array
    {
        return [self::MET, self::BREACHED, self::CANCELED];
    }
}
