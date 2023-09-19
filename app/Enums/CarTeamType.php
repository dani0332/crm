<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class CarTeamType extends Enum
{
    const RENEWALS = 'Renewals';
    const BDM = 'BDM';
    const SBDM = 'SBDM';
    const MOTOR_CORPLINE_RENEWALS = 'Motor Corporate Renewals';
}
