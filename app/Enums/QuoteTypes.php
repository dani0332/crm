<?php

namespace App\Enums;

enum QuoteTypes: string
{
    case BIKE = 'Bike';
    case CYCLE = 'Cycle';

    public function id(): string
    {
        return static::getId($this);
    }

    public static function getId(self $value): int
    {
        return match ($value) {
            QuoteTypes::BIKE => 6,
            QuoteTypes::CYCLE => 10
        };
    }
}
