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
    const UNPAID = 'Unpaid';
    const PARTIALLY_PAID = 'Partially paid';
    const FULL_PAID = 'Fully paid';

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
    const RFTI = 'RFTI'; // Request for tax invoice.
    const RFSOA = 'RFSOA'; // Request for statement of account (SOA).

    // send update log button.
    const SUC = 'Send update to customer'; // send update to customer.
    const SU = 'Book Update'; // send update.
    const SNBU = 'Send and Book Update'; // send and book update.
    const ACTION_SNBU = 'SNBU';
    const ACTION_SUC = 'SUC';
    const ACTION_SU = 'SU';
    const PPE = 'PPE'; // Policy Period Extension
    const BOOKING_FILLED = 1;
    const IS_SEND_UPDATE = 1;
    const POLICY_FILLED = 1;
    const MAOM = 'MAOM'; // Midterm addition of member
    const MD = 'MD'; // Midterm declaration
    const MSC = 'MSC'; // Marital status change
    const PU = 'PU'; // Plan upgrade
    const SC = 'SC'; // Sub-group creation
    const AOLOPFMP = 'AOLOPFMP'; // Addition of location of practice for medical professionals
    const AC = 'AC'; // Additional Cover
    const AL = 'AL'; // Additional location
    const EA = 'EA'; // Employee addition
    const EFMP = 'EFMP'; // Extension for maintenance period
    const I_CLILLR = 'I_CLILLR'; // Increase / Change of limit of indemnity and limit of liability required
    const IEAF_T = 'IEAF_T'; // Increase in estimated annual fees / turnover
    const IISI = 'IISI'; // Increase in sum Insured
    const RFTC = 'RFTC'; // Request for travel certificate
    const AAI = 'AAI'; // Add additional insured
    const AOC = 'AOC'; // Addition of clauses, it's for bike and business lob
    const COA = 'COA'; // Change of address
    const AOCOV = 'AOCOV'; // Add optional cover, it's for car lob
    const COE = 'COE'; // Change of Emirate
    const CISC = 'CISC'; // Change in seating capacity
    const CISC_NFI = 'CISC_NFI'; // Change in seating capacity (with no financial impact)
    const COE_NFI = 'COE_NFI'; // Change of Emirates (with no financial impact)
    const ADD_OPTIONAL_COVER = 'Add optional cover';
    const CIED_EOP = 'CIED_EOP'; // Change in expiry date / Extension of policy
    const RF_SOAC = 'RF_SOAC'; // Request for statement of account (SOA)
    const MSC_NFI = 'MSC_NFI'; // Marital status change (with no financial impact)
    const CTD_NFI = 'CTD_NFI'; // Change travel dates (with no financial impact)
    const DOV = 'DOV'; // Deletion of vehicle
    const ACB = 'ACB'; // Additional commission booking
    const ATIB = 'ATIB'; // Additional tax invoice booking
}
