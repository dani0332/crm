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
    public const PAYMENT_GATEWAY_TAP = 3;

    public static function getName($gatewayId)
    {
        switch ($gatewayId) {
            case self::PAYMENT_GATEWAY_CHECKOUT:
                return 'checkout';
            case self::PAYMENT_GATEWAY_TAP:
                return 'tap';
        }

        return null;
    }
}
