<?php

namespace App\Enums;

enum QuoteTypes: string
{
    case LIFE = 'Life';
    case BIKE = 'Bike';
    case CYCLE = 'Cycle';
    case PET = 'Pet';
    case YACHT = 'Yacht';
    case JETSKI = 'Jetski';
    case CAR_REVIVAL = 'CarRevival';

    public function id(): string
    {
        return static::getId($this);
    }

    public static function getId(self $value): int
    {
        return match ($value) {
            QuoteTypes::LIFE => 4,
            QuoteTypes::BIKE => 6,
            QuoteTypes::CYCLE => 10,
            QuoteTypes::PET => 9,
            QuoteTypes::YACHT => 7,
            QuoteTypes::JETSKI => 11,
        };
    }
}
