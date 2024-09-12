<?php

namespace App\Enums\PDMigrations;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class DealStageEnum extends Enum
{
    const LEAD = 'Lead';
    const QUALIFIED = 'Qualified';
    const RENEWAL = 'Renewal';
    const TERMS_RECEIVED = 'Terms Received';
    const TERMS_AVAILABLE = 'Terms Available';
    const RENEWAL_TERMS_RECEIVED = 'Renewal Terms Received';
    const RENEWAL_TERMS_REVD = 'Renewal Terms Rcvd.';
    const ALLOCATED = 'Allocated';
    const ALLOCATION = 'Allocation';
    const RENEWAL_TERMS_SENT = 'Renewal Terms Sent';
    const TERMS_SENT = 'Terms Sent';
    const QUOTED = 'Quoted';
    const QUOTED_TERMS_SENT = 'Quoted/Terms Sent';
    const FOLLOWED_UP = 'Followed Up';
    const ENGAGED = 'Engaged';
    const FOR_FOLLOW_UP = 'For Follow Up';
    const AUTO_FOLLOW_UP = 'Auto Follow Up';
    const FOLLOW_UP = 'Follow Up';
    const LAST_FOLLOW_UP = 'Last Follow Up';
    const FIRST_FOLLOW_UP = 'First Follow Up';
    const IN_NEGOTIATION = 'In Negotiation';
    const APPLICATION_PENDING = 'Application Pending';
    const ACCEPTED = 'Accepted';
    const APPLICATION = 'Application';
    const FOR_APPLICATION = 'For Application';
    const MISSING_DOCUMENTS_REQUESTED = 'Missing Documents Requested';
    const DOCUMENTS_REQUESTED = 'Documents Requested';
    const APPLICATION_SUBMITTED = 'Application Submitted';
    const WITH_UW = 'With UW';
    const UW_QUOTATION = 'UW Quotation';
    const PAYMENT_PENDING = 'Payment Pending';
    const FOR_PAYMENT = 'For Payment';
    const PAYMENT_LINK_SENT = 'Payment Link Sent';
    const PENDING_PAYMENT = 'Pending Payment';
    const POLICY_DOCUMENTS_PENDING = 'Policy Documents Pending';
    const PENDING_POLICY_DOCS = 'Pending Policy Docs.';
    const PENDING_POLICY = 'Pending Policy';
    const DOCUMENTS = 'Documents';
    const PENDING_POLICY_DOCUMENTS = 'Pending Policy Documents';
    const POLICY_ISSUED = 'Policy Issued';
    const ISSUANCE = 'Issuance';
    const GROUP_EBP_SERVICE = 'Group EBP Service';
    const LOST = 'Lost';
    const LOST_CASES = 'Lost Cases';
    const FAKE = 'Fake';
    const TEST_LEADS = 'Test Leads';
    const HANGING_LEADS = 'Hanging Leads';
    const POLICY_CANCELLED = 'Policy Cancelled';
    const CANCELLATION_PENDING = 'Cancellation Pending';
    const SENT_FOR_TRANSACTION_APPROVAL = 'Sent for Transaction Approval';
    const TRANSACTION_DECLINED = 'Transaction Declined';
    const AML_SCREENING_CLEARED = 'AML Screening Cleared';
    const AML_SCREENING_FAILED = 'AML Screening Failed';
    const KYC_CLEARED = 'KYC Cleared';
    const TRANSACTION_APPROVED = 'Transaction Approved';
    const DUPLICATE = 'Duplicate';
    const OPEN = 'Open';
    const WON = 'Won';
    const LEAD_IN = 'Lead In';
    const HOT_POT = 'Hot Pot';
    const SALES_OPPORTUNITY = 'Sales Opportunity';
    const UNADDRESSED = 'Unaddressed';
    const APR = 'Apr';
    const MAY = 'May';
    const DEC = 'Dec';
    const SEPT = 'Sept';
    const FEB = 'Feb';
    const MARCH = 'March';
    const MAR = 'Mar';
    const JULY = 'July';
    const JAN = 'Jan';
    const AUG = 'Aug';
    const QUOTES_SENT = 'Quotes Sent';
    const PENDING_RENEWAL_INFO = 'Pending Renewal Info';
    const DEATILS_PROPOSAL_FROM_REQUESTED = 'Details/Proposal Form Requested';
    const PROPOSAL_FROM_SENT = 'Proposal Form Sent';
    const PROPOSAL_SENT = 'Proposal Sent';
    const PROPOSAL_FROM_RECIVIED = 'Proposal Form Received';
    const ADDITIONAL_INFORMATION_REQUESTED = 'Additional Information Requested';
    const QUOTES_RENEWAL_TERMS_REQ = 'Quotes/Renewal Terms Req.';
    const QUOTES_REQUEST_UW = 'Quotes Request (UW)';
    const LEAD_ALLOCATED = 'Lead Allocated';
    const REMINDER_SENT = 'Reminder Sent';
    const AWAITING_DOCUMENTS = 'Awaiting Documents';
    const FINALIZING_TERMS_CONDITIONS = 'Finalizing Terms and Conditions';
    const FIRST_FOLLOWUP = 'first followup';
    const ADDITONAL_INFORMATION_REQUESTED = 'Additonal Information Requested';

    public function getToLowerCase(): string
    {
        return strtolower($this->value);
    }
}
