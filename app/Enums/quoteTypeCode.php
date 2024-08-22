<?php

namespace App\Enums;

use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CycleQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\JetskiQuote;
use App\Models\LifeQuote;
use App\Models\PetQuote;
use App\Models\TravelQuote;
use App\Models\YachtQuote;
use BenSampo\Enum\Enum;

class quoteTypeCode extends Enum
{
    const Car = 'Car';
    const Home = 'Home';
    const Health = 'Health';
    const Life = 'Life';
    const Business = 'Business';
    const Bike = 'Bike';
    const Yacht = 'Yacht';
    const Travel = 'Travel';
    const Pet = 'Pet';
    const RM_NB = 'Best';
    const RM_SPEED = 'Good';
    const Car_Revival = 'CarRevival';
    const RetailMedical = 'Retail Medical';
    const EBP = 'Entry-Level';
    const CORPLINE = 'CorpLine';
    const GM = 'GM';
    const RM = 'RM';
    const GroupMedical = 'Group Medical';
    const CarQuote = 'CarQuote';
    const HomeQuote = 'HomeQuote';
    const HealthQuote = 'HealthQuote';
    const LifeQuote = 'LifeQuote';
    const BusinessQuote = 'BusinessQuote';
    const BikeQuote = 'BikeQuote';
    const YachtQuote = 'YachtQuote';
    const TravelQuote = 'TravelQuote';
    const PetQuote = 'PetQuote';
    const RenewalsUpload = 'RenewalsUpload';
    const yesText = 'Yes';
    const noText = 'No';
    const business = 'business';
    const WCU = 'Wow-Call';
    const Amt = 'AMT';
    const Cycle = 'Cycle';
    const Jetski = 'Jetski';
    const Aml = 'Aml';
    const TRA = 'TRA';

    public static function getName($value)
    {
        return match ($value) {
            CarQuote::class => self::Car,
            HomeQuote::class => self::Home,
            HealthQuote::class => self::Health,
            LifeQuote::class => self::Life,
            BusinessQuote::class => self::Business,
            BikeQuote::class => self::Bike,
            YachtQuote::class => self::Yacht,
            TravelQuote::class => self::Travel,
            PetQuote::class => self::Pet,
            CycleQuote::class => self::Cycle,
            JetskiQuote::class => self::Jetski,
        };
    }
}
