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
}
