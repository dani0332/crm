<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class quoteStatusCode extends Enum
{
    const completed = 'completed';
    const pending = 'pending';
    const rejected = 'rejected';
    const approved = 'approved';
    const approvalRequired = 'approvalRequired';
    const AMLScreeningCleared = 'AMLScreeningCleared';
    const AMLScreeningFailed = 'AMLScreeningFailed';
    const NEW_LEAD = 'New Lead';
    const TRANSACTION_APPROVED = 'transaction_approved';
    const NEWLEAD = 'New Lead';
    const QUOTED = 'Quoted';
    const FOLLOWEDUP = 'Followed Up';
    const NEGOTIATION = 'In Negotiation';
    const PAYMENTPENDING = 'Payment Pending';
    const PENDINGUW = 'Pending with UW';
    const POLICY_DOCUMENTS_PENDING = 'Policy Documents Pending';
    const APPLICATION_PENDING = 'Application Pending';
    const APPLICATION_SUBMITTED = 'Application Submitted';
    const MISSING_DOCUMENTS = 'Missing Documents Requested';
    const QUALIFIED = 'Qualified';
    const TRANSACTIONAPPROVED = 'Transaction Approved';
    const FAKE = 'Fake';
    const GROUP_MEDICAL = 'Group Medical';
    const QualificationPending = 'Qualification Pending';
    const CAR_SOLD = 'Car Sold';
    const UNCONTACTABLE = 'Uncontactable';
    const PolicyBooked = 'PolicyBooked';
    const PolicySentToCustomer = 'PolicySentToCustomer';
    const LOST = 'Lost';
    const ALLOCATED = 'Allocated';
    const RENEWAL_TERMS_RECEIVED = 'Renewal Terms Received';
    const PROPOSAL_FORM_REQUESTED = 'Proposal Form Requested';
    const PROPOSAL_FORM_RECEIVED = 'Proposal Form Received';
    const PENDING_RENEWAL_INFORMATION = 'Pending Renewal Information';
    const ADDITIONAL_INFORMATION_REQUESTED = 'Additional Information Requested';
    const QUOTE_REQUESTED = 'Quote Requested';
    const FINALIZING_TERMS = 'Finalizing Terms';
    const QUOTED_BY_UW = 'Quoted By UW';
    const SENT_FOR_TRANSACTION_APPROVAL = 'Sent For Transaction Approval';
    const CANCELLATION_PENDING = 'Cancellation Pending';
    const POLICY_SENT_TO_CUSTOMER = 'Policy Sent To Customer';
    const POLICY_BOOKED = 'Policy Booked';
    const POLICY_CANCELLED = 'Policy Cancelled';
    const POLICY_ISSUED = 'Policy Issued';
    const POLICY_BOOKING_QUEUED = 'Policy Booking Queued';
    const POLICY_BOOKING_FAILED = 'Policy Booking Failed';
}
