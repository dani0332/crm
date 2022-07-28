<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class CarPlanFeaturesCode extends Enum
{
    const TPL_LIABILITY = 'tplLiability';
    const TPL_DAMAGE_LIMIT = 'tplDamageLimit';
    const TPL_DAMAGE_LIMIT_TEXT = 'third party damage limit';
    const DAMAGE_LIMIT = 'damageLimit';
}
