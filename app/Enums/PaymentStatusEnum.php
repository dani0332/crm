<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use Illuminate\Support\Str;

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
    public const PAYMENT_LINK_REQUESTED = 18;

    public static function getPaidStatuses()
    {
        return [
            self::AUTHORISED,
            self::PAID,
            self::CAPTURED,
        ];
    }

    public static function getDeclinedOrFailedStatuses()
    {
        return [
            self::DECLINED,
            self::FAILED,
        ];
    }

    public static function getCancelledDeclinedOrFailedStatuses(): array
    {
        return [
            self::CANCELLED,
            self::DECLINED,
            self::FAILED,
        ];
    }

    public static function getConfirmedOrSettledPaymentStatuses(): array
    {
        return [
            self::AUTHORISED,
            self::PAID,
            self::CAPTURED,
            self::PARTIAL_CAPTURED,
            self::PARTIALLY_PAID,
        ];
    }

    /**
     * Build [{ value, label }] for dropdowns using asArray() as the source of truth.
     */
    public static function withLabels(): array
    {
        return collect(self::asArray())
            ->map(fn ($value, string $name) => [
                'value' => $value,
                'label' => Str::title(Str::lower(str_replace('_', ' ', $name))),
            ])
            ->values()
            ->toArray();
    }
}
