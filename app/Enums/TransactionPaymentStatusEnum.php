<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class TransactionPaymentStatusEnum extends Enum
{
    const UNPAID_TEXT = 'Unpaid';
    const PARTIALLY_PAID_TEXT = 'Partially Paid';
    const FULLY_PAID_TEXT = 'Fully Paid';
    const PAID_TEXT = 'Paid';
}
