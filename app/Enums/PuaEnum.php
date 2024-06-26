<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PuaEnum extends Enum
{
    const PPUA_TYPE = 'PPUA';
    const PPUA_TOOLTIP = 'Premium is offered based on Sukoon\'s Weekly Promotion, Please get the approval for the same with the underwriters.';
    const PENDING_UNDERWRITER_APPROVAL_TOOLTIP = 'Pending Underwriter Approval (PUA) indicates that this quote is prepared using our internal rating calculator. Please contact the client to get the required documents, to proceed with generating a quote on the insurer portal and connect with the underwriter to obtain their approval.';
}
