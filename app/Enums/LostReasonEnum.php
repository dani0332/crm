<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LostReasonEnum extends Enum
{
    public const UNRESPONSIVE_VIA_EMAIL = 1;
    public const ALREADY_PURCHASED_INSURANCE_ELSEWHERE = 5;
    public const SHOPPING_AROUND = 20;
    public const OUTSIDE_OF_BUDGET = 19;
    public const NO_VALID_VISA = 25;
    public const UNINSURABLE_DUE_TO_MEDICAL_REASONS = 29;
    public const UNINSURABLE_DUE_TO_AGE = 30;
}
