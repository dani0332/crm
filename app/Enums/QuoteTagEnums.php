<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class QuoteTagEnums extends Enum
{
    public const POLICY_SENT_TO_CUSTOMER = 'PSTC';
    public const POLICY_BOOKED_ON_SAGE = 'PBOS';
}
