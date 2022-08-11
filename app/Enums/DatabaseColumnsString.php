<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class DatabaseColumnsString extends Enum
{
    const QUOTE_STATUS_ID = 'quote_status_id';
    const MOBILE = 'mobile_no';
    const EMAIL = 'email';
    const CAR_VALUE = 'car_value';
}
