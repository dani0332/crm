<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
class DocumentTypeCode extends Enum
{
    const KYCDOC = 'KYCDOC';
    const SEND_UPDATE_POLICY_SCHEDULE = 'SUPS';
    const SEND_UPDATE_POLICY_CERTIFICATE = 'SUPC';
    const SEND_UPDATE_TAX_INVOICE = 'SUTAXINV';
    const SEND_UPDATE_TAX_INVOICE_RAISED_BUYER = 'SUTAXINVRB';
}
