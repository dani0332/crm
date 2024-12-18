<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PaymentGatewayIdEnum extends Enum
{
    public const CHECKOUT_PAYMENT_GATEWAY = 2;
    public const TAP_PAYMENT_GATEWAY = 3;
}
