<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class QuoteDocumentsEnum extends Enum
{
    public const CAR_POLICY_CERTIFICATE = 'CPC';
    public const POLICY_SCHEDULE = 'CPS';
    public const CAR_TAX_INVOICE = 'CTI';
    public const CAR_TAX_INVOICE_RAISE_BY_BUYER = 'CTIRBB';
    public const CAR_TAX_CREDIT = 'CTC';
    public const CAR_TAX_CREDIT_RAISE_BY_BUYER = 'CTCRBB';
    public const CAR_EMIRATE_ID = 'CEID';
    public const DRIVING_LICENSE = 'DL';
    public const FINAL_TERMS_AND_CONDITIONS = 'CTC';
    public const POLICY_HANDBOOK = 'PHB';
    public const EP = 'EP';
    public const RECEIPT = 'RECEIPT';
    public const CAR_MULKIY = 'CAR_MULKIY';

    // Life Quote
    public const LIFE_POLICY_SCHEDULE = 'PS_LIFE';
    public const LIFE_POLICY_CERTIFICATE = 'CPC';
    public const LIFE_POLICY_HANDBOOK = 'PHB';
    public const LIFE_TAX_INVOICE = 'CTI';
    public const LIFE_TAX_INVOICE_RAISE_BY_BUYER = 'CTIRBB';
    public const LIFE_HEALTH_QUESTIONNAIRE = 'LIFE_HEALTH_QUESTIONNAIRE';

    // Risk Score Document Type
    public const SCRDOC = 'SCRDOC';

    // Travel Quote
    public const TRAVEL_POLICY_SCHEDULE = 'CPS_TRVL';
    public const TRAVEL_TAX_INVOICE = 'TI';
    public const TRAVEL_TAX_INVOICE_RAISE_BY_BUYER = 'CTIRBB';
    public const TRAVEL_POLICY_CERTIFICATE = 'CPC';

    // Savings Quote
    public const SAVINGS_POLICY_SCHEDULE = 'PS_SAV';
    public const SAVINGS_POLICY_CERTIFICATE = 'PC_SAV';
    public const SAVINGS_APPLICATION_COPY = 'AC_SAV';
    public const SAVINGS_TAX_INVOICE = 'TI';
    public const SAVINGS_TAX_INVOICE_RAISE_BY_BUYER = 'CTIRBB';
    public const SAVINGS_RECEIPT = 'SPDR';
    public const SAVINGS_ADDITIONAL_EMAIL_ATTACHMENTS = 'TAEA';
    // End of Savings Quote

    public const CAR_REGISTRATION_CARD = 'CAR_MULKIY';

    public static function getSukoonAllDocTypes(): array
    {
        return [self::CAR_TAX_INVOICE, self::POLICY_SCHEDULE, self::CAR_TAX_INVOICE_RAISE_BY_BUYER];
    }

    public static function getSukoonInitialDocTypes(): array
    {
        return [self::CAR_TAX_INVOICE, self::POLICY_SCHEDULE];
    }
}
