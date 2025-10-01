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

    public function isFinal(): bool
    {
        return match ($this) {
            self::ACTIVE => false,
            self::MET, self::BREACHED, self::CANCELED => true,
        };
    }
}
