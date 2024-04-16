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
    const ISSUING_DOCUMENTS = 'ISSUING_DOCUMENTS';
    const TAX_INVOICE = 'Tax Invoice';
    const TAX_INVOICE_RAISED_BY_BUYER = 'Tax Invoice Raised By Buyer';
    const NETWORK_LIST_BUSINESS = 'NL_GH';
    const Receipt_BUSINESS = 'REC_GH';
    const CPD = 'CPD';
    const BPD = 'BPD';
    const TPD = 'TPD';
    const HPD = 'HPD';
    const LPD = 'LPD';
    const HOMPD = 'HOMPD';
    const CYCPD = 'CYCPD';
    const CLPD = 'CLPD';
    const GMQPD = 'GMQPD';
    const PPD = 'PPD';
    const YPD = 'YPD';
}
