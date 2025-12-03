<?php

namespace App\Enums;

enum KycSourceOfIncomeEnum: string
{
    case EMPLOYED = 'employed';
    case BUSINESS = 'business';

    public function label(): string
    {
        return match ($this) {
            self::EMPLOYED => 'Employed',
            self::BUSINESS => 'Business',
        };
    }

    public static function options(): array
    {
        return [
            self::EMPLOYED->value => self::EMPLOYED->label(),
            self::BUSINESS->value => self::BUSINESS->label(),
        ];
    }
}
