<?php

namespace App\Enums;

enum PaymentTermEnum: int
{
    case MONTHLY = 12;
    case QUARTERLY = 4;
    case SEMI_ANNUALLY = 2;
    case ANNUALLY = 1;
}
