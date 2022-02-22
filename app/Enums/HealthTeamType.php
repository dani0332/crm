<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class HealthTeamType extends Enum
{
    const EBP = "RBP";
    const RM_NB = "RM-NB";
    const RM_SPEED = "RM-SPEED";
    const GROUP_MEDICAL = "Group Medical";
}
