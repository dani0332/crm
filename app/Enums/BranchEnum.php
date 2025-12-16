<?php

namespace App\Enums;

enum BranchEnum: int
{
    case DUBAI = 1;
    case ABU_DHABI = 2;

    public function name(): string
    {
        return match ($this) {
            self::DUBAI => 'Dubai & Northern Emirates',
            self::ABU_DHABI => 'Abu Dhabi',
        };
    }
}
