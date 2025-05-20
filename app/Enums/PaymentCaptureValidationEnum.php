<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PaymentCaptureValidationEnum extends Enum
{
    const SUCCESS = 'CAPTURE_VALIDATION_CLEARED';
    const FAILED = 'CAPTURE_VALIDATION_FAILED';
}
