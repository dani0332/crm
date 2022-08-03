<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class RenewalsUploadType extends Enum
{
    const CREATE_LEADS = 'create';
    const UPDATE_LEADS = 'update';
}
