<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class SendUpdateLogStatusEnum extends Enum
{
    const NEW_REQUEST = 'New Request';
    const REQUEST_IN_PROGRESS = 'Request in progress';
    // const PAYMENT_PENDING = 'Payment Pending';
    // const PAYMENT_AUTHORIZED = 'Payment Authorized';
    // const SENT_FOR_TRANSACTION_APPROVAL = 'Sent for Transaction Approval';
    const TRANSACTION_DECLINE = 'Transaction Declined';
    const TRANSACTION_APPROVED = 'Transaction Approved';
    // const STALE_REQUEST = 'Stale Request';
    const UPDATE_SENT_TO_CUSTOMER = 'Update Sent to Customer';
    const UPDATE_BOOKED = 'Update Booked';
}
