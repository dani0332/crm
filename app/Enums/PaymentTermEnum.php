<?php

namespace App\Enums;

enum PaymentTermEnum: int
{
    case MONTHLY = 12;
    case QUARTERLY = 4;
    case SEMI_ANNUALLY = 2;
    case ANNUALLY = 1;

    case SINGLE_PAYMENT = -1;

    public static function asArray(): array
    {
        return array_reduce(self::cases(), function ($carry, $case) {
            $carry[$case->name] = $case->value;

            return $carry;
        }, []);
    }
}
