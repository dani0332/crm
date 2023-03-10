<?php

namespace App\Enums;

enum QuoteTypes: string
{
    case BIKE = 'Bike';
    case CYCLE = 'Cycle';
    case YACHT = 'Yacht';

    public function id(): string
    {
        return static::getId($this);
    }

    public static function getId(self $value): int
    {
        return match ($value) {
            QuoteTypes::BIKE => 6,
            QuoteTypes::YACHT => 7,
            QuoteTypes::CYCLE => 10
        };
    }
}
