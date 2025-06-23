<?php

namespace App\Enums;

enum RangeLookupCodeEnum: string
{
    case LANDLORD_RENTING_OUT = 'landlord_renting_out';

    /**
     * Get the value of an enum case
     */
    public function value(): string
    {
        return $this->value;
    }
}
