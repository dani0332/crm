<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class BusinessTypeOfInsuranceIdEnum extends Enum
{
    const GROUP_MEDICAL = 5;
    const GROUP_LIFE = 6;
    const MARINE_CARGO_INDIVIDUAL_SHIPMENT = 10;
    const MARINE_CARGO_OPEN_COVER = 33;
    const MARINE_HULL = 11;
    const PROPERTY = 3;
    const SEVERAL_INSURANCES = 1;
    const CAR_FLEET = 9;
    const TRADE_CREDIT = 16;
    const POLITICAL_VIOLENCE = 37;
}
