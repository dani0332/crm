<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class QuoteStatusEnum extends Enum
{
    public const Draft = 1;
    public const Quoted = 2;
    public const Cancelled = 3;
    public const Issued = 4;
    public const AMLScreeningCleared = 6;
    public const AMLScreeningFailed = 7;
    public const NewLead = 8;
    public const Fake = 9;
    public const FTCSent = 10;
    public const FTCAccepted = 11;
    public const FTCResubmitted = 12;
    public const MissingDocumentsRequested = 14;
    public const TransactionApproved = 15;
    public const Lost = 17;
    public const KYCCleared = 19;
    public const FTCPending = 22;
    public const FollowedUp = 24;
    public const InNegotiation = 25;
    public const ApplicationPending = 26;
    public const ApplicationSubmitted = 36;
    public const PendingwithUW = 27;
    public const PaymentPending = 28;
    public const PolicyDocumentsPending = 29;
    public const QualificationPending = 30;
    public const Qualified = 31;
    public const TransactionDeclined = 32;
    public const PolicyIssued = 33;
    public const PolicyInvoiced = 34;
    public const Duplicate = 35;
    public const PriceTooHigh = 40;
    public const PolicyPurchasedBeforeFirstCall = 41;
    public const NotContactablePe = 42;
    public const FollowupCall = 43;
    public const Interested = 44;
    public const NoAnswer = 45;
    public const NotInterested = 46;
    public const NotEligibleForInsurance = 47;
    public const IMRenewal = 48;
    public const NotLookingForMotorInsurance = 49;
    public const NonGccSpec = 50;
    public const PendingQuote = 51;
    public const CarSold = 52;
    public const Uncontactable = 53;
    public const Stale = 54;
    public const PolicySentToCustomer = 70;
    public const PolicyBooked = 71;
    public const CancellationPending = 57;
    public const PolicyCancelled = 58;
    public const Allocated = 59;
    public const RenewalTermsReceived = 60;
    public const ProposalFormRequested = 61;
    public const ProposalFormReceived = 62;
    public const PendingRenewalInformation = 63;
    public const AdditionalInformationRequested = 64;
    public const QuoteRequested = 65;
    public const FinalizingTerms = 66;
    public const QuotedByUW = 67;
    public const SentForTransactionApproval = 68;
    public const RenewalTermsSent = 69;
    public const PolicyPending = 72;
    public const EarlyRenewal = 73;
    public const PolicyCancelledReissued = 74;
    public const POLICY_BOOKING_QUEUED = 75;
    public const POLICY_BOOKING_FAILED = 76;

    // This is use for lost reason id not for Quote status
    public const LOSTREASONID = 34;
}
