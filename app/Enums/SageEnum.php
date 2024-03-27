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

    // Sage Request Types Start
    // Creation of Customer
    const SRT_CREATE_CUSTOMER = 'CREATE_CUSTOMER';

    // Creation of Prepayment Receipt
    const SRT_CREATE_PP_REC = 'CREATE_PP_REC';
    const SRT_RTP_PP_REC = 'RTP_PP_REC';
    const SRT_POST_PP_REC = 'POST_PP_REC';

    // Creation of Invoice - Upfront
    const SRT_CREATE_AR_PREM_COMM_INV = 'CREATE_AR_PREM_COMM_INV';
    const SRT_RTP_AR_PREM_COMM_INV = 'RTP_AR_PREM_COMM_INV';
    const SRT_POST_AR_PREM_COMM_INV = 'POST_AR_PREM_COMM_INV';
    const SRT_CREATE_AP_PREM_INV = 'CREATE_AP_PREM_INV';
    const SRT_RTP_AP_PREM_INV = 'RTP_AP_PREM_INV';
    const SRT_POST_AP_PREM_INV = 'POST_AP_PREM_INV';
    const SRT_CREATE_AR_DISC_INV = 'CREATE_AR_DISC_INV';
    const SRT_RTP_AR_DISC_INV = 'RTP_AR_DISC_INV';
    const SRT_POST_AR_DISC_INV = 'POST_AR_DISC_INV';

    // Creation of Invoice - Monthly, Quaterly, Semi-Annual, Split, Custom
    const SRT_CREATE_AR_SPPAY_INV = 'CREATE_AR_SPPAY_INV';
    const SRT_RTP_AR_SPPAY_INV = 'RTP_AR_SPPAY_INV';
    const SRT_POST_AR_SPPAY_INV = 'POST_AR_SPPAY_INV';

    // Mapping of Prepayment to Invoice - Upfront
    const SRT_PP_REC_ONE_INV = 'PRE_PAY_REC_ONE_INV';
    const SRT_RTP_PP_REC_ONE_INV = 'RTP_PP_REC_ONE_INV';
    const SRT_POST_PP_REC_ONE_INV = 'POST_PP_REC_ONE_INV';

    // Mapping of Prepayment to Invoice - Split
    const SRT_PP_REC_SPINV = 'PRE_PAY_REC_SPINV';
    const SRT_RTP_PP_REC_SPINV = 'RTP_PP_REC_SPINV';
    const SRT_POST_PP_REC_SPINV = 'POST_PP_REC_SPINV';

    // Mapping of Prepayment to Invoice - Monthly, Quaterly, Semi-Annual, Custom
    const SRT_PP_REC_CFQ_INV = 'PRE_PAY_REC_CFQ_INV';
    const SRT_RTP_PP_REC_CFQ_INV = 'RTP_PP_REC_CFQ_INV';
    const SRT_POST_PP_REC_CFQ_INV = 'POST_PP_REC_CFQ_INV';

    // Creation of Reversal & Correction Invoice
    const SRT_CREATE_AR_PREM_COMM_REV_INV = 'CREATE_AR_PREM_COMM_REV_INV';
    const SRT_RTP_AR_PREM_COMM_REV_INV = 'RTP_AR_PREM_COMM_REV_INV';
    const SRT_POST_AR_PREM_COMM_REV_INV = 'POST_AR_PREM_COMM_REV_INV';
    const SRT_CREATE_AR_PREM_COMM_CORR_INV = 'CREATE_AR_PREM_COMM_CORR_INV';
    const SRT_RTP_AR_PREM_COMM_CORR_INV = 'RTP_AR_PREM_COMM_CORR_INV';
    const SRT_POST_AR_PREM_COMM_CORR_INV = 'POST_AR_PREM_COMM_CORR_INV';
    const SRT_CREATE_AP_PREM_REV_INV = 'CREATE_AP_PREM_REV_INV';
    const SRT_RTP_AP_PREM_REV_INV = 'RTP_AP_PREM_REV_INV';
    const SRT_POST_AP_PREM_REV_INV = 'POST_AP_PREM_REV_INV';
    const SRT_CREATE_AP_PREM_CORR_INV = 'CREATE_AP_PREM_CORR_INV';
    const SRT_RTP_AP_PREM_CORR_INV = 'RTP_AP_PREM_CORR_INV';
    const SRT_POST_AP_PREM_CORR_INV = 'POST_AP_PREM_CORR_INV';
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
    // Sage Request Types End

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
}
