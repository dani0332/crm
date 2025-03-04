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
    public const PAYMENT_GATEWAY_CHECKOUT = 2;
    public const PAYMENT_GATEWAY_CHECKOUT_TEXT = 'checkout';
    public const PAYMENT_GATEWAY_TAP = 3;
    public const PAYMENT_GATEWAY_TAP_TEXT = 'tap';
}
