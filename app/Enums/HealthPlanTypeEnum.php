<?php

namespace App\Enums;

enum HealthPlanTypeEnum: int
{
    use Enumable;

    case ENTRY_LEVEL = 1;
    case GOOD = 2;
    case BEST = 3;

    public static function typeName(int $type): self
    {
        return match ($type) {
            1 => self::ENTRY_LEVEL,
            2 => self::GOOD,
            3 => self::BEST
        };
    }
}
