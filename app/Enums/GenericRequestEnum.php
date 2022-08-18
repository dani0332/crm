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
    public const Yes = 'Yes';
    public const No = 'No';
    public const TPA_Code = 'tpa';
    public const TypeString = 'string';
    public const SelectString = 'Select';
    public const CheckboxString = 'Checkbox';
    public const INTEGER = 'integer';
    public const NotApplicable = 'N/A';
}
