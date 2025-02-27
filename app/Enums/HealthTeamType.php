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
    const EBP = 'Entry-Level';
    const RM_NB = 'Best';
    const RM_SPEED = 'Good';
    const GROUP_MEDICAL = 'Group Medical';
    const PCP = 'PCP';
}
