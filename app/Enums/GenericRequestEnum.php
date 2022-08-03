<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class GenericRequestEnum extends Enum
{
    const Yes = 'Yes';
    const No = 'No';
    const TPA_Code = 'tpa';
    const TypeString = 'string';
    const Selectstring = 'Select';
    const CheckboxString = 'Checkbox';
}
