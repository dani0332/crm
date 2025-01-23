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

    // Life Quote
    public const LIFE_POLICY_SCHEDULE = 'PS_LIFE';
    public const LIFE_POLICY_CERTIFICATE = 'CPC';
    public const LIFE_POLICY_HANDBOOK = 'PHB';
    public const LIFE_TAX_INVOICE = 'CTI';
    public const LIFE_TAX_INVOICE_RAISE_BY_BUYER = 'CTIRBB';

    // Risk Score Document Type
    public const SCRDOC = 'SCRDOC';

    // Travel Quote
    public const TRAVEL_POLICY_SCHEDULE = 'CPS_TRVL';
    public const TRAVEL_TAX_INVOICE = 'TI';
    public const TRAVEL_TAX_INVOICE_RAISE_BY_BUYER = 'CTIRBB';
    public const TRAVEL_POLICY_CERTIFICATE = 'CPC';
}
