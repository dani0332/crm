<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class SageEnums extends Enum
{
    // Endpoints
    const END_POINT_AR_CUSTOMER = 'AR/ARCustomers';

    // Error Codes
    const ERROR_RECORD_DUPLICATE = 'RecordDuplicate';

    // Status
    const STATUS_SUCCESS = 'success';
    const STATUS_FAIL = 'fail';

    // Sage Document Types
    const DOCUMENT_TYPE_CREATE_AR_INVOICE = 'CREATE_AR_INVOICE';
    const DOCUMENT_TYPE_CREATE_AR_INVOICE_DIS = 'CREATE_AR_INVOICE_DIS';
    const DOCUMENT_TYPE_CREATE_AP_INVOICE = 'CREATE_AP_INVOICE';
    const DOCUMENT_TYPE_CREATE_PRE_PAYMENT_RECEIPT = 'CREATE_PRE_PAYMENT_RECEIPT';
    const DOCUMENT_TYPE_APPLY_PRE_PAYMENT_RECEIPT = 'APPLY_PRE_PAYMENT_RECEIPT';

    // Send Update Type to Sage
    const SEND_UPDATE_NORMAL = 'SEND_UPDATE_NORMAL';
    const SEND_UPDATE_CORRECTION = 'SEND_UPDATE_CORRECTION';
    const SEND_UPDATE_REVERSAL = 'SEND_UPDATE_REVERSAL';

    // Functions
    const TYPE_SEND_POLICY = 'Send Policy';
    const TYPE_CREATE_RECEIPT = 'Create Receipt';
    const TYPE_SEND_UPDATE = 'Send Update';

}
