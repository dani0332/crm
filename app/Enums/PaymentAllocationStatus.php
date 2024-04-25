<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PaymentAllocationStatus extends Enum
{
    const NOT_ALLOCATED = 'not_allocated';
    const PARTIALLY_ALLOCATED = 'partially_allocated';
    const FULLY_ALLOCATED = 'fully_allocated';
    const UNPAID = 'unpaid';
}
