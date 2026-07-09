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
    /** IMCRM / grid list filters: sentinel value — not persisted on domain models. */
    public const ALL = 'ALL';

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
    const SEND_UPDATE_QUOTE_TYPE_MARSHAL = 99; // this quote type pass to Marshal Service for capture payment, not added on quote type enums because getting conflict while calling quotes.
    public const ERROR = 'ERROR';
    const FAILED = 'failed';
    const PASSED = 'passed';
    const TYPE_LEAD = 'lead';
    const TYPE_ENDORSEMENT = 'endorsement';
    const DEFAULT_NATIONALITY = 56;
    const EBAO_QUOTE_STATUS = 'Quote';
    const EBAO_UW_APPROVAL_STATUS_NO = 'N';
    const SUCCESS = 'success';
    const CALL_TYPE_QUOTE_INFO = 'quoteInfo';
    const API_ISSUANCE_STATUS_ID_BLANK = 'blank';
    const PREVIOUS_POLICY_EXPIRED = 'Previous policy has expired';
    const PREVIOUS_POLICY_EXPIRED_STATUS_ID = 99;
    const SEND_UPDATE_LOG = 'SendUpdateLog';
    public const TRADE_LICENSE = 'tradeLicense';
    public const EMIRATES_ID = 'emiratesId';
    public const PASSPORT = 'passport';
    public const TRADE_LICENSE_SHORT_CODE = 'TL';
    public const EMIRATES_ID_SHORT_CODE = 'EID';
    const UNKNOWN_ERROR = 'Unknown error';
    const SEND_UPDATE_AS_QUOTE_TYPE = 'sendupdate';
    const QUOTE_INITIATED = 'Quote Initiated';
    const QUOTE_FINALIZED = 'Quote Finalized';
    const TRAVEL_SENIOR_MEMBER_AGE = 65;
    const HIGH_RISK_SCORE = 35;
    const LUMPSUM = 'Lumpsum';
}
