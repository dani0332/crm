<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class SendPolicyTypeEnum extends Enum
{
    const CUSTOMER = 'customer';
    const CUSTOMER_BUTTON_TEXT = 'Send Policy To Customer';
    const SAGE = 'sage';
    const SAGE_BUTTON_TEXT = 'Send and Book Policy';
}
