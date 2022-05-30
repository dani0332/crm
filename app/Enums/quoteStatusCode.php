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
    const completed = "completed";
    const pending = "pending";
    const rejected = "rejected";
    const approved = "approved";
    const approvalRequired = "approvalRequired";
    const AMLScreeningCleared = "AMLScreeningCleared";
    const AMLScreeningFailed = "AMLScreeningFailed";
    const NEW_LEAD = "New Lead";
    const TRANSACTION_APPROVED = "transaction_approved";
    const NEWLEAD = "New Lead";
    const QUOTED = "Quoted";
    const FOLLOWEDUP = "Followed Up";
    const NEGOTIATION = "In Negotiation";
    const PAYMENTPENDING = "Payment Pending";
    const PENDINGUW = "Pending with UW";
    const PLOICY_DOCUMENTS_PENDING = "Policy Documents Pending";
    const APPLICATION_PENDING = "Application Pending";
    const MISSING_DOCUMENTS = "Missing Documents Requested";
    const QUALIFIED = "Qualified";
    const TRANSACTIONAPPROVED = "Transaction Approved";
    const FAKE = "Fake";
    const GROUPMEDICAL= 'Group Medical';
}
