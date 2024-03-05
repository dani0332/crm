<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class QuoteIssuanceStatusEnum extends Enum
{
    const PortalDown = 1;
    const WaitingForClientConfirmation = 2;
    const IssueFound = 3;
    const UnderwriterIssuance = 4;
    const PortalIssuance = 5;
    const PolicyAlreadyIssuedByTheUnderwriter = 6;
    const RenewalDirectToUnderwriter = 7;
    const Other = 8;
}
