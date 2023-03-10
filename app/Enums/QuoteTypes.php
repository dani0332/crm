<?php

namespace App\Enums;

enum QuoteTypes: string
{
    case BIKE = 'Bike';
    case CYCLE = 'Cycle';
    case PET = 'Pet';

    /**
     * @return string
     */
    public function id(): string
    {
        return static::getId($this);
    }

    /**
     * @param  QuoteTypes  $value
     * @return int
     */
    public static function getId(self $value): int
    {
        return match ($value) {
            QuoteTypes::BIKE => 6,
            QuoteTypes::CYCLE => 10,
            QuoteTypes::PET => 9
        };
    }
}
