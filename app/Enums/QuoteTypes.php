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
    case PERSONAL = 'Personal';
    case GROUP_MEDICAL = 'Group Medical';
    case CORPLINE = 'CorpLine';
    case CAR_REVIVAL = 'CarRevival';

    public function id(): string
    {
        return self::getId($this);
    }

    public static function getId(self $value): int
    {
        return match ($value) {
            QuoteTypes::CAR => 1,
            QuoteTypes::HOME => 2,
            QuoteTypes::HEALTH => 3,
            QuoteTypes::LIFE => 4,
            QuoteTypes::BUSINESS => 5,
            QuoteTypes::BIKE => 6,
            QuoteTypes::YACHT => 7,
            QuoteTypes::TRAVEL => 8,
            QuoteTypes::PET => 9,
            QuoteTypes::CYCLE => 10,
            QuoteTypes::JETSKI => 11,
            QuoteTypes::CORPLINE => 101,
            QuoteTypes::GROUP_MEDICAL => 102,
        };
    }
    public static function getName($value)
    {
        $types = [
            1 => QuoteTypes::CAR,
            2 => QuoteTypes::HOME,
            3 => QuoteTypes::HEALTH,
            4 => QuoteTypes::LIFE,
            5 => QuoteTypes::BUSINESS,
            6 => QuoteTypes::BIKE,
            7 => QuoteTypes::YACHT,
            8 => QuoteTypes::TRAVEL,
            9 => QuoteTypes::PET,
            10 => QuoteTypes::CYCLE,
            11 => QuoteTypes::JETSKI,
            101 => QuoteTypes::CORPLINE,
            102 => QuoteTypes::GROUP_MEDICAL,
        ];

        return $types[$value];
    }

    public static function getIdFromValue(string $value): ?int
    {
        return self::getId(match (ucfirst($value)) {
            'Car' => QuoteTypes::CAR,
            'Home' => QuoteTypes::HOME,
            'Health' => QuoteTypes::HEALTH,
            'Life' => QuoteTypes::LIFE,
            'Business' => QuoteTypes::BUSINESS,
            'Bike' => QuoteTypes::BIKE,
            'Yacht' => QuoteTypes::YACHT,
            'Travel' => QuoteTypes::TRAVEL,
            'Pet' => QuoteTypes::PET,
            'Cycle' => QuoteTypes::CYCLE,
            'Jetski' => QuoteTypes::JETSKI,
            'CorpLine' => QuoteTypes::CORPLINE,
            'Group Medical' => QuoteTypes::GROUP_MEDICAL,
            default => null,
        });
    }
}
