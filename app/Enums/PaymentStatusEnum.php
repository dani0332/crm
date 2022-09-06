<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PaymentStatusEnum extends Enum
{
    const PENDING = 1;
    const CANCELLED = 3;
    const AUTHORISED = 4;
    const DECLINED = 5;
    const CAPTURED = 6;
    const REFUNDED = 7;
    const STARTED = 8;
    const FAILED = 9;
    const PAID = 10;
    const DRAFT = 11;
}
