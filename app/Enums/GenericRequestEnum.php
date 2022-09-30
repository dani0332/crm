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
    public const EMAIL = 'email';
    public const MOBILE_NO = 'mobile_no';
    public const RECORD_PURPOSE = 'record_only';
    public const FEMALE_SINGLE = 'Female-Single';
    public const FEMALE_MARRIED = 'Female-Married';
    public const FEMALE_SINGLE_VALUE = 'FS';
    public const FEMALE_MARRIED_VALUE = 'FM';
    public const MALE_SINGLE = 'Male';
    public const MALE_SINGLE_VALUE = 'M';
}
