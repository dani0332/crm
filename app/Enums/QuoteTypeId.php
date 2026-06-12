<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use ReflectionClass;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class QuoteTypeId extends Enum
{
    const Car = 1;
    const Home = 2;
    const Health = 3;
    const Life = 4;
    const Business = 5;
    const Bike = 6;
    const Yacht = 7;
    const Travel = 8;
    const Pet = 9;
    const Cycle = 10;
    const Jetski = 11;
    const Corpline = 101;
    const GroupMedical = 102;
    const Savings = 18;
    const CompanyCar = 15;
    const TradeCredit = 12;
    const JobLoss = 16;
    const JBLS = 17;
    const Cyber = 19;
    const Device = 20;

    /**
     * Quote type IDs that use watermarked policy schedule document URL when available.
     * Add new LOBs here when they support watermarked policy schedule (e.g. cyber/device-style).
     */
    public static function quoteTypesUsingWatermarkedPolicySchedule(): array
    {
        return [self::Cyber];
    }

    /**
     * Quote type IDs that use watermarked policy certificate document URL when available.
     * Add new LOBs here when they support watermarked policy certificate (e.g. cyber/device-style).
     */
    public static function quoteTypesUsingWatermarkedPolicyCertificate(): array
    {
        return [self::Device]; // no LOBs using watermarked policy certificate water marked doc url yet
    }

    public static function getOptions()
    {
        $oClass = new ReflectionClass(__CLASS__);
        $constants = $oClass->getConstants();
        $retval = [];
        foreach ($constants as $name => $val) {
            $retval[$val] = $name;
        }

        return $retval;
    }

    /**
     * Line of business / invoice display label for a quote type.
     * For Device quotes, returns device_type (e.g. smartphone, tablet) when present.
     *
     * @param  int|string  $quoteTypeIdOrCode  QuoteTypeId constant or quoteTypeCode constant
     * @param  string  $fallback  Default label (e.g. quoteTypeCode value)
     * @param  string|null  $deviceType  Device-specific type string from the quote
     */
    public static function displayLabel(int|string $quoteTypeIdOrCode, string $fallback, ?string $deviceType = null): string
    {
        $isDevice = $quoteTypeIdOrCode === self::Device
            || $quoteTypeIdOrCode === quoteTypeCode::Device;

        if ($isDevice) {
            return $deviceType !== null ? ucfirst(strtolower(string: $deviceType)) : $fallback;
        }

        return $fallback;
    }

    /**
     * Get display name for quote type ID
     */
    public static function getDisplayName(?int $quoteTypeId): ?string
    {
        $options = self::getOptions();

        return $options[$quoteTypeId] ?? null;

    }

    /**
     * Get all quote type IDs for claim documents
     */
    public static function getClaimDocumentQuoteTypes(): array
    {
        return [
            self::Yacht,
            self::Travel,
            self::Pet,
            self::Cycle,
            self::Life,
            self::Home,
            self::Health,
            self::Business,
            self::Jetski,
        ];
    }
}
