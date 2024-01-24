<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
class DocumentTypeCode extends Enum
{
    const KYCDOC = 'KYCDOC';
    const OD = 'OD';
}
