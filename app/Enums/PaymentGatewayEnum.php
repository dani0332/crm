<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentGatewayEnum extends Enum
{
    public const PAYMENT_GATEWAY_CHECKOUT = 2;
    public const PAYMENT_GATEWAY_TAP = 3;
    public const PAYMENT_GATEWAY_PAYMENT_LINK = null;

    public static function getPaymentGatewayPaymentLink()
    {
        $env = config('constants.APP_ENV', 'production');

        return $env !== 'production' ? 5 : 4;
    }

    public static function getName($gatewayId)
    {
        switch ($gatewayId) {
            case self::PAYMENT_GATEWAY_CHECKOUT:
                return 'checkout';
            case self::PAYMENT_GATEWAY_TAP:
                return 'tap';
            case self::getPaymentGatewayPaymentLink():
                return 'payment_link';
        }

        return null;
    }
}
