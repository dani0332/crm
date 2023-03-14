<?php

namespace App\Enums;

enum QuoteTypes: string
{
    case BIKE = 'Bike';
    case CYCLE = 'Cycle';
    case YACHT = 'Yacht';
    case JETSKI = 'Jetski';

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
            QuoteTypes::YACHT => 7,
            QuoteTypes::CYCLE => 10,
            QuoteTypes::JETSKI => 11,
        };
    }
}
