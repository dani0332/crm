<?php

namespace App\Enums;

enum BranchEnum: int
{
    case DUBAI = 1;
    case ABU_DHABI = 2;

    public function name(): string
    {
        return match ($this) {
            self::DUBAI => 'Dubai',
            self::ABU_DHABI => 'Abu Dhabi',
        };
    }
}
