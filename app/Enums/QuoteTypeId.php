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
}
