<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class HomePossessionType extends Enum
{
    const LANDLORD = 1;
    const TENANT = 2;
}
