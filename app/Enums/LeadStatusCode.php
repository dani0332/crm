<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class LeadStatusCode extends Enum
{
    const TRANSACTION_APPROVED = 'Transaction Approved';
    const LOST = 'Lost';
    const Qualified = 'Qualified';
}
