<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class AMLStatusCode extends Enum
{
    const AMLPending = 'AML_PENDING';
    const AMLScreeningCleared = 'AML_SCREENING_CLEARED';
    const AMLScreeningFailed = 'AML_SCREENING_FAILED';

    public static function getName($value)
    {
        $statuses = [
            'AML_PENDING' => 'AML Pending',
            'AML_SCREENING_CLEARED' => 'AML Screening Cleared',
            'AML_SCREENING_FAILED' => 'AML Screening Failed',
        ];

        return $statuses[$value] ?? 'AML Pending';
    }
}
