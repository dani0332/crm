<?php

namespace App\Enums;

enum QuoteTypes: string
{
    case CAR = 'Car';
    case HOME = 'Home';
    case HEALTH = 'Health';
    case LIFE = 'Life';
    case BUSINESS = 'Business';
    case BIKE = 'Bike';
    case YACHT = 'Yacht';
    case TRAVEL = 'Travel';
    case PET = 'Pet';
    case CYCLE = 'Cycle';
    case JETSKI = 'Jetski';
    case AMT = 'Amt';
    case GROUP_MEDICAL = 'Group Medical';
    case CORPLINE = 'Corpline';

    public function id(): string
    {
        return self::getId($this);
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
            QuoteTypes::CAR => 1,
        };
    }
}
