<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use ReflectionClass;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
class quoteTypeCode extends Enum
{
    const Car = "Car";
    const Home = "Home";
    const Health = "Health";
    const Life = "Life";
    const Business = "Business";
    const Bike = "Bike";
    const Yacht = "Yacht";
    const Travel = "Travel";
    const RM_NB = "RM-NB";
    const RM_SPEED = "RM-SPEED";
    const RetailMedical = "Retail Medical";
    const EBP = "EBP";
    const CORPLINE = "CORPLINE";
    const GM = "GM";
    const RM = "RM";
    const GroupMedical = "Group Medical";
    const CarQuote = "CarQuote";
    const HomeQuote = "HomeQuote";
    const HealthQuote = "HealthQuote";
    const LifeQuote = "LifeQuote";
    const BusinessQuote = "BusinessQuote";
    const BikeQuote = "BikeQuote";
    const YachtQuote = "YachtQuote";
    const TravelQuote = "TravelQuote";

    public static function getOptions() 
    {
        $oClass = new ReflectionClass(__CLASS__);
        $constants = $oClass->getConstants();
        $retval = array();
        foreach($constants as $name => $val) {
                $retval[$val] = $name;
        }
        return $retval;
    }
}
