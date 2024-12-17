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
    const SEND_UPDATE_POLICY_SCHEDULE = 'SUPS'; // Send Update Policy Schedule
    const SEND_UPDATE_POLICY_CERTIFICATE = 'SUPC'; // Send Update Policy Certificate
    const SEND_UPDATE_ECARD = 'SUECARD'; // Send Update E-Card
    const SEND_UPDATE_TAX_INVOICE = 'SUTAXINV'; // Send Update Tax Invoice
    const SEND_UPDATE_TAX_INVOICE_RAISED_BUYER = 'SUTAXINVRB'; // Send Update Tax Invoice Raised Buyer
    const SEND_UPDATE_RECEIPT = 'SURECEIPT'; // Send Update Receipt
    const SEND_UPDATE_ADDITIONAL_EMAIL_ATTACHEMENTS = 'SUAEA'; // Send Update Additional Email Attachments
    const SEND_UPDATE_GARAGE_LIST = 'SUGL'; // Send Update Garage List
    const SEND_UPDATE_POLICY_HANDBOOK = 'SUPHBOOK'; // Send Update Policy Handbook
    const SEND_UPDATE_NETWORK_LIST = 'SUNL'; // Send Update Network List -- TODO:: need to be created.
    const SEND_UPDATE_SIGNED_MED_APP_FORM = 'SUSMAFORM'; // Send Update Network List -- TODO:: need to be created.
    const SEND_UPDATE_APP_COPY = 'SUAPCOPY'; // Send Update Network List -- TODO:: need to be created.
    const SEND_UPDATE_PAYMENT_PROOF = 'SUPP'; // Payment Proof
    const SEND_UPDATE_CUSTOMER_DOCUMENTS = 'SUCD'; // Customer documents
    const SEND_UPDATE_UW_EMAIL_CORRESPONDENCE = 'SUUWEC'; // UW email Correspondence
    const ISSUING_DOCUMENTS = 'ISSUING_DOCUMENTS';
    const SEND_UPDATE_AUDIT_RECORD = 'SUAR'; // Send Update Audit Record
    const QUOTE = 'QUOTE';
    const MEMBER = 'MEMBER';
    const ENDORSEMENT_DOCUMENTS = 'ENDORSEMENT_DOCUMENTS';
    const SEND_UPDATE = 'SEND_UPDATE';
    const QUOTE_AND_ENDORSEMENT = 'QUOTE_AND_ENDORSEMENT';
    const EP = 'EMBEDDED_PRODUCT';

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
    const CLPDR = 'CLPDR';
    const CLDPDR = 'CLDPDR';
    const GMQPD = 'GMQPD';
    const GMQPDR = 'GMQPDR';
    const GMQDPDR = 'GMQDPDR';
    const PPD = 'PPD';
    const YPD = 'YPD';
    const PPR = 'PPR';
    const CTIRBB = 'CTIRBB'; // Tax Invoice Raised By Buyer
    const TI = 'TI'; // Tax Invoice
    const COMPANY_BUSINESS_TYPE_OF_CUSTOMER = 'CBTC'; // Tax Invoice
    const INDIVIDUAL_BUSINESS_TYPE_OF_CUSTOMER = 'IBTC'; // Tax Invoice
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
    const QD = 'QD';
    const AML = 'AML';
    const E_TICKETS = 'E_TICKETS';
    const AUDIT = 'AUDIT';
}
