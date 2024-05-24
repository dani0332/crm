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

    // This is the same as the one in the database and we are using this as a text not it's code
    // The reason behind this code is different for all lob's but text is sames that's why we are using this as a text
    const NETWORK_LIST_BUSINESS = 'NL_GH';
    const Receipt_BUSINESS = 'REC_GH';
    const OD = 'OD';
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
    const CTIRBB = 'CTIRBB'; // Tax Invoice Raised By Buyer
    const TI = 'TI'; // Tax Invoice
    const CPD_RECEIPT = 'CPDR';
    const BPD_RECEIPT = 'BPDR';
    const TPD_RECEIPT = 'TPDR';
    const HPD_RECEIPT = 'HPDR';
    const LPD_RECEIPT = 'LPDR';
    const HOMPD_RECEIPT = 'HOMPDR';
    const CYCPD_RECEIPT = 'CYCPDR';
    const CLPD_RECEIPT = 'CLPDR';
    const GMQPD_RECEIPT = 'GMQPDR';
    const PPD_RECEIPT = 'PPDR';
    const YPD_RECEIPT = 'YPDR';
}
