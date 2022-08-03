<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class AmlSearchType extends Enum
{
    const ENTITY = 'Entity';
    const INDIVIDUAL = 'Individual';
}
