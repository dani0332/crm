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
    const SEND_UPDATE = 'SEND_UPDATE';
    const EF = 'EF';    // Endorsement Financial
    const EN = 'EN';    // Endorsement Non Financial
    const CI = 'CI';    // Cancellation from Inception
    const CIR = 'CIR';  // Cancellation from Inception and Reissuance
    const CPD = 'CPD';  // Correction of Policy Details
    const MPC = 'MPC'; //Midterm policy cancellation
    const MDOM = 'MDOM'; //Midterm deletion of member
    const MDOV = 'MDOV'; //Midterm deletion of vehicle
    const ED = 'ED'; //Employee deletion
    const DM = 'DM'; //Delete member
    const CPU = 'CPU'; // Correction of Policy Upload.
    const CAA = 'CAA'; // Correction and amendments. 
    const EIU = 'EIU'; // Emirates ID update. 
    const MSCNFI = 'MSCNFI'; // Marital status change (with no financial impact).
    const RFCOC = 'RFCOC'; // Request for certificate of continuity.
    const RFCOI = 'RFCOI'; // Request for certificate of insurance.
    const WOWPA = 'WOWPA'; // Waive off waiting period applied.
    const QR = 'QR'; // Quote request.
    const RFAML = 'RFAML'; // Request for active member list.
    const RFEC = 'RFEC'; // Request for ecard copy.
    const RTI = 'RTI'; // Request for tax invoice.
    const RFSOA = 'RFSOA'; // Request for statement of account (SOA).
    // send update log button. 
    const SUC = 'Send Update to Customer'; // send update to customer. 
    const SU = 'Send Update'; // send update. 
    const PPE = 'PPE'; // Policy Period Extension
}
