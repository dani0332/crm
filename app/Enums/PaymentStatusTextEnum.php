<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PaymentStatusTextEnum extends Enum
{
    public const PAID_TEXT = 'PAID';
    public const NEW_TEXT = 'NEW';
    public const PARTIALLY_PAID_TEXT = 'PARTIALLY_PAID';
}
