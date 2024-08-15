<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class BusinessTypeOfInsuranceEnum extends Enum
{
    const LIVESTOCK_INSURANCE = 'Livestock Insurance';
    const MARINE_CARGO_OPEN_COVER = 'Marine Cargo - Open Cover';
    const HOLIDAY_HOME = 'Holiday Homes';
    const GOODS_IN_TRANSIT = 'Goods In Transit';
}
