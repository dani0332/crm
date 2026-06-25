<?php

namespace App\Enums;

use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CyberQuote;
use App\Models\CycleQuote;
use App\Models\DeviceQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\JetskiQuote;
use App\Models\LifeQuote;
use App\Models\PetQuote;
use App\Models\SavingsQuote;
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
    const Life_Revival = 'LifeRevival';
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
    const SAVINGS = 'Savings';
    const Marine = 'Marine';
    const CompanyCar = 'CompanyCar';
    const Device = 'Device';
    const CYBER = 'Cyber';
    const Smartphone = 'Smartphone';

    /** Please stop using this class instead use App\Enums\QuoteTypes (native PHP Enums) */
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
            SavingsQuote::class => self::SAVINGS,
            DeviceQuote::class => self::Device,
            CyberQuote::class => self::CYBER,
        };
    }

    public static function getProductNameFromQuoteTypeCode(string $quoteTypeCode): string
    {
        return match ($quoteTypeCode) {
            self::CYBER => TeamNameEnum::CYBER,
            self::Device => TeamNameEnum::DEVICE,
            default => $quoteTypeCode,
        };
    }

    public static function getQuoteTypeCodeFromProductName(string $productName): string
    {
        return match ($productName) {
            TeamNameEnum::CYBER => self::CYBER,
            TeamNameEnum::DEVICE => self::Device,
            default => $productName,
        };
    }

    public static function resolveQuoteType(string $quoteTypeCode): string
    {
        return ucfirst($quoteTypeCode) === self::Device
            ? self::Smartphone
            : $quoteTypeCode;
    }
}
