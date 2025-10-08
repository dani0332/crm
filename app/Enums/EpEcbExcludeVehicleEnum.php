<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use ReflectionClass;

final class EpEcbExcludeVehicleEnum extends Enum
{
    public const MAKE_CODE_LAMBORGHINI = 10297632;
    public const MAKE_CODE_MCLAREN = 10299137;
    public const MAKE_CODE_FERRARI = 10296083;
    public const MAKE_CODE_ROLLS_ROYCE = 10300288;
    public const MAKE_CODE_BENTLEY = 10302389;
    public const MAKE_CODE_KOENIGSEGG = 10304235;

    public const MAKE_CODE_MERCEDES = 10298234;
    public const MAKE_CODE_VOLKSWAGEN = 10303399;
    public const MAKE_CODE_PORSCHE = 10300050;
    public const MAKE_CODE_TESLA = 10300536;
    public const MAKE_CODE_EXCEED = 10304379;
    public const MAKE_CODE_INEOS = 10304376;
    public const MAKE_CODE_JETOUR = 10304373;
    public const MAKE_CODE_LINCOLN = 10297652;

    public static function getOptions() 
    {
        $oClass = new ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

    public static function getMakeCodes() 
    {
        return array_values(self::getOptions());
    }
}