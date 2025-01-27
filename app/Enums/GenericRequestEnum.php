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
    public const FEMALE_SHORT_VALUE = 'F';
    public const PENDING = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';
    public const TRUE = 'True';
    public const FALSE = 'False';
    public const DISABLED_ATTRIBUTE = 'disabled';
    public const ASSIGN_WITHOUT_EMAIL = 1;
    public const ASSIGN_WITH_EMAIL = 2;
    public const MAX_DAYS = 'MAX_DAYS_CAR_REPORTS';
    public const FEMALE = 'Female';
    public const EXPORT_PLAN_DETAIL = 'export-plan-detail';
    public const EXPORT_LEADS_DETAIL_WITH_EMAIL_MOBILE = 'export-leads-detail-with-email-mobile';
    public const EXPORT_MAKES_MODELS = 'export-makes-models';
    public const MEMBER = 'Member';
    public const BIKE = 'BIKE';
    public const MOTOR_BIKE = 'MOTOR BIKE';
    public const MOTORBIKE = 'MOTORBIKE';
    const SEND_UPDATE_QUOTE_TYPE_MARSHAL = 99; // this quote type pass to Marshal Service for capture payment, not added on quote type enums because geting conflict while calling qutoes.
    public const ERROR = 'ERROR';
    const FAILED = 'failed';
}
