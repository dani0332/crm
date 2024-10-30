<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PolicyIssuanceStatusEnum extends Enum
{
    const PortalDown = 1;
    const WaitingForClientConfirmation = 2;
    const IssueFound = 3;
    const UnderwriterIssuance = 4;
    const PortalIssuance = 5;
    const PolicyAlreadyIssuedByTheUnderwriter = 6;
    const RenewalDirectToUnderwriter = 7;
    const Other = 8;
    const PolicyIssued = 9;

    const POLICY_DETAIL_API_FAILED = 10;
    const UPLOAD_POLICY_DOCUMENTS_API_FAILED = 11;
    const BOOKING_DETAILS_API_FAILED = 12;
}
