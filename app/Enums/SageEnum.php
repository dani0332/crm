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
    const SRT_CREATE_CUSTOMER = 'CREATE_CUSTOMER'; // AR/ARCustomers

    // Creation of Prepayment Receipt
    const SRT_CREATE_PP_REC = 'CREATE_PP_REC'; // AR/ARReceiptAndAdjustmentBatches
    const SRT_RTP_PP_REC = 'RTP_PP_REC'; // AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber='')
    const SRT_POST_PP_REC = 'POST_PP_REC'; // AR/ARPostReceiptsAndAdjustments/123

    // Creation of Invoice - Upfront
    const SRT_CREATE_AR_PREM_COMM_INV = 'CREATE_AR_PREM_COMM_INV'; // AR/ARInvoiceBatches
    const SRT_RTP_AR_PREM_COMM_INV = 'RTP_AR_PREM_COMM_INV'; // AR/ARInvoiceBatches(1234)
    const SRT_POST_AR_PREM_COMM_INV = 'POST_AR_PREM_COMM_INV'; // AR/ARPostInvoices/123

    const SRT_CREATE_AP_PREM_INV = 'CREATE_AP_PREM_INV'; // AP/APInvoiceBatches
    const SRT_RTP_AP_PREM_INV = 'RTP_AP_PREM_INV'; // AP/APInvoiceBatches(213444)
    const SRT_POST_AP_PREM_INV = 'POST_AP_PREM_INV'; // AP/APPostInvoices/123

    const SRT_CREATE_AR_DISC_INV = 'CREATE_AR_DISC_INV'; // AR/ARInvoiceBatches
    const SRT_RTP_AR_DISC_INV = 'RTP_AR_DISC_INV'; // AR/ARInvoiceBatches(1234)
    const SRT_POST_AR_DISC_INV = 'POST_AR_DISC_INV'; // AR/ARPostInvoices/123

    // Creation of Invoice - Monthly, Quaterly, Semi-Annual, Split, Custom
    const SRT_CREATE_AR_SPPAY_INV = 'CREATE_AR_SPPAY_INV'; // AR/ARInvoiceBatches
    const SRT_RTP_AR_SPPAY_INV = 'RTP_AR_SPPAY_INV'; // AR/ARInvoiceBatches(1234)
    const SRT_POST_AR_SPPAY_INV = 'POST_AR_SPPAY_INV'; // AR/ARPostInvoices/123

    // Mapping of Prepayment to Invoice - Upfront
    const SRT_PP_REC_ONE_INV = 'PRE_PAY_REC_ONE_INV'; // AR/ARReceiptAndAdjustmentBatches
    const SRT_RTP_PP_REC_ONE_INV = 'RTP_PP_REC_ONE_INV'; // AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber='')
    const SRT_POST_PP_REC_ONE_INV = 'POST_PP_REC_ONE_INV'; // AR/ARPostReceiptsAndAdjustments/123
    
    // Mapping of Prepayment to Invoice - Split
    const SRT_PP_REC_SPINV = 'PRE_PAY_REC_SPINV'; // AR/ARReceiptAndAdjustmentBatches
    const SRT_RTP_PP_REC_SPINV = 'RTP_PP_REC_SPINV'; // AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber='')
    const SRT_POST_PP_REC_SPINV = 'POST_PP_REC_SPINV'; // AR/ARPostReceiptsAndAdjustments/123
    
    // Mapping of Prepayment to Invoice - Monthly, Quaterly, Semi-Annual, Custom
    const SRT_PP_REC_CFQ_INV = 'PRE_PAY_REC_CFQ_INV'; // AR/ARReceiptAndAdjustmentBatches
    const SRT_RTP_PP_REC_CFQ_INV = 'RTP_PP_REC_CFQ_INV'; // AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber='')
    const SRT_POST_PP_REC_CFQ_INV = 'POST_PP_REC_CFQ_INV'; // AR/ARPostReceiptsAndAdjustments/123
    // Sage Request Types End

    // Process Types
    const PT_BOOK_POLICY = 'BOOK_POLICY';
    const PT_CREATE_RECEIPT = 'CREATE_RECEIPT';
    const PT_SEND_UPDATE = 'SEND_UPDATE';

    // Send Update Types
    const SUT_NORMAL = 'SU_NORMAL';
    const SUT_REVE_CORR = 'SU_REVE_CORR';

}
