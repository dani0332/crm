<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class InsuranceProvderConstants extends Enum
{
    const PLANNAME = 'CarPlan';
    const PLANADDON = 'CarPlanAddOn';
    const NAME = 'InsuranceProvider';
    const COVERAGE = 'CarPlanCoverage';
}
