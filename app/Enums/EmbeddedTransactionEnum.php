<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EmbeddedTransactionEnum extends Enum
{
    /* Statuses are using for SukoonMedex Process */
    const STATUS_QUEUED = 'Queued';
    const STATUS_NEW_POLICY = 'New policy';
    const STATUS_QUOTED = 'Quoted';
    const STATUS_PAYMENT_SUCCEED = 'Payment succeeded';
    const STATUS_BOOKED = 'Documents retrieved';
    const STATUS_READY_FOR_SAGE = 'Ready for sage';
    const STATUS_FAILED = 'Failed';

    public static function getRemainingPolicyStatus(string $policyStatus = ''): array
    {
        return match ($policyStatus) {
            self::STATUS_NEW_POLICY => [self::STATUS_QUOTED, self::STATUS_PAYMENT_SUCCEED, self::STATUS_BOOKED, self::STATUS_READY_FOR_SAGE],
            self::STATUS_QUOTED => [self::STATUS_PAYMENT_SUCCEED, self::STATUS_BOOKED, self::STATUS_READY_FOR_SAGE],
            self::STATUS_PAYMENT_SUCCEED => [self::STATUS_BOOKED, self::STATUS_READY_FOR_SAGE],
            self::STATUS_BOOKED => [self::STATUS_READY_FOR_SAGE],
            self::STATUS_READY_FOR_SAGE => [],
            default => [self::STATUS_NEW_POLICY, self::STATUS_QUOTED, self::STATUS_PAYMENT_SUCCEED, self::STATUS_BOOKED, self::STATUS_READY_FOR_SAGE],
        };
    }

    public static function checkPolicyStatusPassed(string $policyStatus = '', string $passedPolicyStatus = ''): bool
    {
        return ! in_array($passedPolicyStatus, self::getRemainingPolicyStatus($policyStatus));
    }
}
