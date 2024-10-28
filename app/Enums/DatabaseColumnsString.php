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
    public const QUOTE_STATUS_ID = 'quote_status_id';
    public const MOBILE = 'mobile_no';
    public const EMAIL = 'email';
    public const CAR_VALUE = 'car_value';
    public const RENEWAL_BATCH = 'renewal_batch';
    public const PREVIOUS_QUOTE_POLICY_NUMBER = 'previous_quote_policy_number';
    public const PREVIOUS_QUOTE_POLICY_NUMBER_TEXT = 'previous_quote_policy_number_text';
    public const PREVIOUS_POLICY_EXPIRY_DATE = 'previous_policy_expiry_date';
    public const SOURCE = 'source';
    public const LOST_REASON = 'lost_reason';
    public const CAR_VALUE_TIER = 'car_value_tier';
    public const DATE_OF_BIRTH = 'dob';
    public const CODE = 'code';
    public const INSURER_TAX_INVOICE_NUMBER = 'insurer_tax_number';
    public const INSURER_COMMISSION_TAX_INVOICE_NUMBER = 'insurer_commmission_invoice_number';
}
