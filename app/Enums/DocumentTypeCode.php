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
    const SEND_UPDATE_MYALFRED_OFFERS = 'SUMAOFFERS'; // Send Update My alfred Offers -- TODO:: need to be created.
    const SEND_UPDATE_NETWORK_LIST = 'SUNL'; // Send Update Network List -- TODO:: need to be created.
    const SEND_UPDATE_SIGNED_MED_APP_FORM = 'SUSMAFORM'; // Send Update Network List -- TODO:: need to be created.
    const SEND_UPDATE_APP_COPY = 'SUAPCOPY'; // Send Update Network List -- TODO:: need to be created.
}
