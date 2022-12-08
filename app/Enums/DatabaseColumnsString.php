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
    const RENEWAL_BATCH = 'renewal_batch';
    const PREVIOUS_QUOTE_POLICY_NUMBER = 'previous_quote_policy_number';
    const PREVIOUS_POLICY_EXPIRY_DATE = 'previous_policy_expiry_date';
}
