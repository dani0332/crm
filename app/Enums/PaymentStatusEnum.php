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
    public const PENDING = 1;
    public const CANCELLED = 3;
    public const AUTHORISED = 4;
    public const DECLINED = 5;
    public const CAPTURED = 6;
    public const REFUNDED = 7;
    public const STARTED = 8;
    public const FAILED = 9;
    public const PAID = 10;
    public const DRAFT = 11;
    public const PARTIAL_CAPTURED = 12;
    public const DISPUTED = 13;
    public const NEW = 14;
    public const OVERDUE = 15;
    public const CREDIT_APPROVED = 16;
    public const PARTIALLY_PAID = 17;
}
