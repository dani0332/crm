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
    const CPD = 'CPD';
    const BPD = 'BPD';
    const TPD = 'TPD';
    const HPD = 'HPD';
    const LPD = 'LPD';
    const HOMPD = 'HOMPD';
    const CYCPD = 'CYCPD';
    const CLPD = 'CLPD';
    const CLPDR = 'CLPDR';
    const CLDPDR = 'CLDPDR';
    const GMQPD = 'GMQPD';
    const GMQPDR = 'GMQPDR';
    const GMQDPDR = 'GMQDPDR';
    const PPD = 'PPD';
    const YPD = 'YPD';
    const CTIRBB = 'CTIRBB'; // Tax Invoice Raised By Buyer
    const TI = 'TI'; // Tax Invoice
    const COMPANY_BUSINESS_TYPE_OF_CUSTOMER = 'CBTC'; // Tax Invoice
    const INDIVIDUAL_BUSINESS_TYPE_OF_CUSTOMER = 'IBTC'; // Tax Invoice
}
