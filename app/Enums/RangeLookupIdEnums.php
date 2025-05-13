<?php

namespace App\Enums;

enum RangeLookupIdEnums: int
{
    case LANDLORD_RENTING_OUT = 2;

    /**
     * Get the value of an enum case
     */
    public function value(): int
    {
        return $this->value;
    }
}
