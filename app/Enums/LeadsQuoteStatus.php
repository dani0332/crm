<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class LeadsQuoteStatus extends Enum
{
    const NEWLEAD = "New Lead";
    const QUOTED = "Quoted";
    const FOLLOWEDUP = "Followed Up";
    const NEGOTIATION = "In Negotiation";
    const PAYMENTPENDING = "Payment Pending";
    const PENDINGUW = "Pending with UW";
    const PLOICY_DOCUMENTS_PENDING = "Policy Documents Pending";
    const TRANSACTION_APPROVED = "Transaction Approved";
    const APPLICATION_PENDING = "Application Pending";
    const MISSING_DOCUMENTS = "Missing Documents Requested";
    const QUALIFIED = "Qualified";
}
