<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class EnvEnum extends Enum
{
    const LOCAL = 'local';
    const DEVELOPMENT = 'development';
    const STAGING = 'staging';
    const PRODUCTION = 'production';
}
