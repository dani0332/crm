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
    const SPD_RECEIPT = 'SPDR';
    const QD = 'QD';
    const AML = 'AML';
    const E_TICKETS = 'E_TICKETS';
    const AUDIT = 'AUDIT';
    const TRVLPAS = 'TRVLPAS';
    const Illustration_Document = 'LIFE_ID';
    const CPS = 'CPS'; // Car Policy Schedule
    const CPC = 'CPC'; // Car Policy Certificate

    // HOME SAL
    const HOME_SAL = 'HOME_SAL';
    const PS_SAV = 'PS_SAV';
    const PC_SAV = 'PC_SAV';
    const AC_SAV = 'AC_SAV';
    const PAYMENT_RECEIPT = 'SPD';
    const DRIVING_LICENSE = 'DL';
    const EMIRATES_ID = 'CEID';
    const REGISTRATION_CARD_MULKIYA = 'CAR_MULKIY';
    const POLICY_CERTIFICATE = 'CPC';
    const POLICY_SCHEDULE = 'CPS';

    // BAL
    const BAL = 'BAL';
    const BAL_BIKE = 'BAL_BIKE';
    const GM_BOL = 'GM_BOL';
    const BAL_TRVL = 'BAL_TRVL';
    const BAL_HOME = 'BAL_HOME';
    const BAL_HLTH = 'BAL_HLTH';
    const BAL_YCHT = 'BAL_YCHT';
    const BAL_CYCLE = 'BAL_CYCLE';
    const BAL_LIFE = 'BAL_LIFE';
    const BAL_PET = 'BAL_PET';
    const BOR_SIGN = 'BOR_SIGN';
    const BAL_BS = 'BAL_BS';
    const BUS_BAL = 'BUS_BAL';
    const GH_PS = 'GH_PS'; // Group Health Policy Schedule
    const GH_NL = 'GH_NL'; // Group Health Network List
    const GH_EC = 'GH_EC'; // Group Health E-Card
    const GH_PC = 'GH_PC'; // Group Health Policy Certificate
    const ECARD_HLTH = 'ECARD_HLTH'; // Health E-Card
    const SMAF_HLTH = 'SMAF_HLTH'; // Health Signed medical application form
    const POLC = 'POLC'; // Health Policy Certificate
    const PC_TRVL = 'PC_TRVL'; // Travel Policy Certificate
    const CPS_TRVL = 'CPS_TRVL'; // Travel Policy Schedule
    const PC_YTCH = 'PC_YTCH'; // Yacht Policy Certificate
    const PS_LIFE = 'PS_LIFE'; // Life Policy Schedule
    const AC_LIFE = 'AC_LIFE'; // Life Application Copy
    const PHB = 'PHB'; // Policy Handbook
    const COMP_PH = 'COMP_PH'; // Trade Credit Policy Handbook
    const COMP_AEA = 'COMP_AEA'; // Trade Credit Additional Email Attachments
    const COMP_EC = 'COMP_EC'; // Trade Credit E-Card
    const COMP_PC = 'COMP_PC'; // Trade Credit Policy Certificate
    const COMP_PS = 'COMP_PS'; // Trade Credit Policy Schedule
    const IND_PC = 'IND_PC'; // Group Travel Policy Certificate
    const COMP_POLIC = 'COMP_POLIC'; // Holiday Homes Policy Certificate
    const FIDEL_POC = 'FIDEL_POC'; // Goods In Transit Policy Certificate
    const COM_P_MONE = 'COM_P_MONE'; // Livestock Insurance Policy Schedule
    const COMP_LIVES = 'COMP_LIVES'; // Marine Cargo - Open Cover Policy Schedule
    const COMP_MARIN = 'COMP_MARIN'; // Marine Cargo (individual shipment) insurance Policy Schedule
    const COMP_MONEY = 'COMP_MONEY'; // Livestock Insurance Policy Schedule
    const COMP_Polic = 'COMP_Polic'; // Holiday Homes Policy Schedule
    const FIDEL_POS = 'FIDEL_POS'; // Goods In Transit Policy Schedule
    const IND_PS = 'IND_PS'; // Group Travel Policy Schedule
}
