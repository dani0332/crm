<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AMLStatusCode extends Enum
{
    const AMLPending = 'AML_PENDING';
    const AMLScreeningCleared = 'AML_SCREENING_CLEARED';
    const AMLScreeningFailed = 'AML_SCREENING_FAILED';
    const InsurerAMLScreeningNA = 'N/A';
    const InsurerAMLScreeningPending = 'PENDING';
    const InsurerAMLScreeningCleared = 'CLEARED';
    const InsurerAMLScreeningFailed = 'FAILED';
    const AML_SCREENING_CLEARED_ID = 2;
    const AML_SCREENING_FAILED_ID = 1;
    const AML_PENDING_ID = 1;

    private static $statuses = [
        'AML_PENDING' => 'AML Pending',
        'AML_SCREENING_CLEARED' => 'AML Screening Cleared',
        'AML_SCREENING_FAILED' => 'AML Screening Failed',
        'N/A' => 'N/A',
        'PENDING' => 'Pending',
        'CLEARED' => 'Cleared',
        'FAILED' => 'Failed',
    ];

    public static function getStatuses()
    {
        return self::$statuses;
    }

    public static function getName($value, $defaultValue = 'AML Pending')
    {
        return self::$statuses[$value] ?? $defaultValue;
    }

}
