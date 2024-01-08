<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class QuoteDocumentsEnum extends Enum
{
    public const CAR_POLICY_CERTIFICATE = 'CPC';
    public const POLICY_SCHEDULE = 'CPS';
    public const CAR_TAX_INVOICE = 'END_TI';
    public const CAR_TAX_INVOICE_RAISE_BY_BUYER = 'CTIRBB';
    public const CAR_EMIRATE_ID = 'CEID';
    public const DRIVING_LICENSE = 'DL';
    public const FINAL_TERMS_AND_CONDITIONS = 'CTC';
    public const POLICY_HANDBOOK = 'PHB';
}
