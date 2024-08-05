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
    public const LOCAL = 'local';
    public const DEVELOPMENT = 'development';
    public const STAGING = 'staging';
    public const UAT = 'uat';
    public const PRODUCTION = 'production';
    public const TEST = 'test';
}
