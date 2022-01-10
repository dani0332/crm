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
    const DRIVER_COVER = "driverCover";
    const PASSENGER_COVER = "passengerCover";
    const BREAKDOWN_COVER = "breakdownCover";
}
