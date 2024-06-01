<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class SageEnum extends Enum
{
    // Endpoints
    const END_POINT_AR_CUSTOMER = 'AR/ARCustomers';

    // Error Codes
    const ERROR_RECORD_DUPLICATE = 'RecordDuplicate';

    // Status
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL = 'fail';
    const STATUS_PAID = 'paid';

    // -------------------------------------- Sage Request Types Start --------------------------------------------

    // Creation of Customer
    const SRT_CREATE_CUSTOMER = 'CREATE_CUSTOMER';

    // AR Pre Payments Receipts
    const SRT_CREATE_PP_REC = 'CREATE_PP_REC';
    const SRT_RTP_PP_REC = 'RTP_PP_REC';
    const SRT_POST_PP_REC = 'POST_PP_REC';

    // AR Invoices - Upfront
    const SRT_CREATE_AR_PREM_COMM_INV = 'CREATE_AR_PREM_COMM_INV';
    const SRT_RTP_AR_PREM_COMM_INV = 'RTP_AR_PREM_COMM_INV';
    const SRT_POST_AR_PREM_COMM_INV = 'POST_AR_PREM_COMM_INV';

    // AR Invoices - Monthly, Quaterly, Semi-Annual, Split, Custom
    const SRT_CREATE_AR_SPPAY_INV = 'CREATE_AR_SPPAY_INV';
    const SRT_RTP_AR_SPPAY_INV = 'RTP_AR_SPPAY_INV';
    const SRT_POST_AR_SPPAY_INV = 'POST_AR_SPPAY_INV';
    const SRT_AR_SPPAY_INV_PATCH = 'AR_SPPAY_INV_PATCH';

    // AP Invoices - Upfront
    const SRT_CREATE_AP_PREM_INV = 'CREATE_AP_PREM_INV';
    const SRT_CREATE_AP_SP_PREM_INV = 'CREATE_AP_SP_PREM_INV';
    const SRT_RTP_AP_PREM_INV = 'RTP_AP_PREM_INV';
    const SRT_POST_AP_PREM_INV = 'POST_AP_PREM_INV';

    // AR Discount Invoices
    const SRT_CREATE_AR_DISC_INV = 'CREATE_AR_DISC_INV';
    const SRT_RTP_AR_DISC_INV = 'RTP_AR_DISC_INV';
    const SRT_POST_AR_DISC_INV = 'POST_AR_DISC_INV';

    // Payment Receipt One Invoice - Upfront
    const SRT_CREATE_PAY_REC_ONE_INV = 'CREATE_PAY_REC_ONE_INV';
    const SRT_RTP_PAY_REC_ONE_INV = 'RTP_PAY_REC_ONE_INV';
    const SRT_POST_PAY_REC_ONE_INV = 'POST_PAY_REC_ONE_INV';

    // AR Split Pre payments Receipts - Split
    const SRT_CREATE_AR_SP_PRE_PAYMENT = 'CREATE_AR_SP_PRE_PAYMENT';
    const SRT_RTP_AR_SP_PRE_PAYMENT = 'RTP_AR_SP_PRE_PAYMENT';
    const SRT_POST_AR_SP_PRE_PAYMENT = 'POST_AR_SP_PRE_PAYMENT';

    // Creation of Reversal & Correction Invoice

    // AR Invoice Reverse and Commission
    const SRT_CREATE_AR_PREM_COMM_REV_INV = 'CREATE_AR_PREM_COMM_REV_INV';
    const SRT_RTP_AR_PREM_COMM_REV_INV = 'RTP_AR_PREM_COMM_REV_INV';
    const SRT_POST_AR_PREM_COMM_REV_INV = 'POST_AR_PREM_COMM_REV_INV';
    const SRT_CREATE_AR_PREM_COMM_CORR_INV = 'CREATE_AR_PREM_COMM_CORR_INV';
    const SRT_RTP_AR_PREM_COMM_CORR_INV = 'RTP_AR_PREM_COMM_CORR_INV';
    const SRT_POST_AR_PREM_COMM_CORR_INV = 'POST_AR_PREM_COMM_CORR_INV';

    // AP Invoice Reverse and Commission
    const SRT_CREATE_AP_PREM_REV_INV = 'CREATE_AP_PREM_REV_INV';
    const SRT_RTP_AP_PREM_REV_INV = 'RTP_AP_PREM_REV_INV';
    const SRT_POST_AP_PREM_REV_INV = 'POST_AP_PREM_REV_INV';
    const SRT_CREATE_AP_PREM_CORR_INV = 'CREATE_AP_PREM_CORR_INV';
    const SRT_RTP_AP_PREM_CORR_INV = 'RTP_AP_PREM_CORR_INV';
    const SRT_POST_AP_PREM_CORR_INV = 'POST_AP_PREM_CORR_INV';

    // AR Discount Reverse and Correction
    const SRT_CREATE_AR_DISC_REV_INV = 'CREATE_AR_DISC_REV_INV';
    const SRT_RTP_AR_DISC_REV_INV = 'RTP_AR_DISC_REV_INV';
    const SRT_POST_AR_DISC_REV_INV = 'POST_AR_DISC_REV_INV';
    const SRT_CREATE_AR_DISC_CORR_INV = 'CREATE_AR_DISC_CORR_INV';
    const SRT_RTP_AR_DISC_CORR_INV = 'RTP_AR_DISC_CORR_INV';
    const SRT_POST_AR_DISC_CORR_INV = 'POST_AR_DISC_CORR_INV';
    const SRT_GET_AR_INVOICE = 'GET_AR_INVOICE';
    const SRT_GET_AP_INVOICE = 'GET_AP_INVOICE';
    const SRT_REV_CORR_AR_PREM_COMM_INV = 'SRT_REV_CORR_AR_PREM_COMM_INV';
    const SRT_REV_CORR_AP_PREM_INV = 'SRT_REV_CORR_AP_PREM_INV';
    const SRT_REV_CORR_AR_DIS_INV = 'SRT_REV_CORR_AR_DIS_INV';

    // -------------------------------------- Sage Request Types End --------------------------------------------

    // Process Types
    const PT_BOOK_POLICY = 'BOOK_POLICY';
    const PT_CREATE_RECEIPT = 'CREATE_RECEIPT';
    const PT_SEND_UPDATE = 'SEND_UPDATE';

    // Send Update Types
    const SUT_NORMAL = 'SU_NORMAL';
    const SUT_REVE_CORR = 'SU_REVE_CORR';
    const SCT_STRAIGHT = 'STRAIGHT';
    const SCT_GET_INVOICE = 'GET_INVOICE';
    const SCT_REVERSAL = 'REVERSAL';
    const SCT_CORRECTION = 'CORRECTION';
    const SCT_DISCOUNT = 'DISCOUNT';

    // Payment Frequencies
    const SF_UPFRONT = 'upfront';
    const SF_SPLIT_PAYMENT = 'split_payments';
}
