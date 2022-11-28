<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class CarPlanAddonsCode extends Enum
{
    const DRIVER_COVER = 'driverCover';
    const DRIVER_COVER_TEXT = 'driver cover';
    const PASSENGER_COVER = 'passengerCover';
    const PASSENGER_COVER_TEXT = 'passengers cover';
    const BREAKDOWN_COVER = 'breakdownCover';
    const CAR_HIRE = 'carHire';
    const OMAN_COVER = 'omanCover';
}
