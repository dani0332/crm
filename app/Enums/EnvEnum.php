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
    // For Production Environment
    public const PRODUCTION = 'production';

    // For Pre Prod Environment
    public const STAGING = 'staging';

    // For Server Development & Testing Environment
    public const TEST = 'test';
    public const UAT = 'uat';
    public const DEVELOPMENT = 'development';

    // For Local Development & Testing Environment
    public const LOCAL = 'local';
}
