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
    const Draft = 1;
    const Quoted = 2;
    const Cancelled = 3;
    const Issued = 4;
    const AMLScreeningCleared = 6;
    const AMLScreeningFailed = 7;
    const NewLead = 8;
    const Fake = 9;
    const FTCSent = 10;
    const FTCAccepted = 11;
    const FTCResubmitted = 12;
    const MissingDocumentsRequested = 14;
    const TransactionApproved = 15;
    const Lost = 17;
    const KYCCleared = 19;
    const FTCPending = 22;
    const FollowedUp = 24;
    const InNegotiation = 25;
    const ApplicationPending = 26;
    const PendingwithUW = 27;
    const PaymentPending = 28;
    const PolicyDocumentsPending = 29;
    const QualificationPending = 30;
    const Qualified = 31;
    const TransactionDeclined = 32;
    const PolicyIssued = 33;
    const PolicyInvoiced = 34;
    const Duplicate = 35;
    const PriceTooHigh = 40;
    const PolicyPurchasedBeforeFirstCall = 41;
    const NotContactablePe = 42;
    const FollowupCall = 43;
    const Interested = 44;
    const NoAnswer = 45;
    const NotInterested = 46;
    const NotEligibleForInsurance = 47;
    const IMRenewal = 48;
    const NotLookingForMotorInsurance = 49;
    const NonGccSpec = 50;
    const PendingQuote = 51;
    const Stale = 54;
    const CarSold = 52;
    const Uncontactable = 53;
    const PolicyBooked = 56;
    const PolicyCancelled = 58;
}
