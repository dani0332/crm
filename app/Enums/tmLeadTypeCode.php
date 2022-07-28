<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class tmLeadTypeCode extends Enum
{
    const Organic = 'Organic';
    const Revival = 'Revival';
    const Recycle = 'Recycle';
    const Aqeed = 'Aqeed';
    const Aqeedrenewal = 'Aqeed renewal';
    const Aqeedrevival = 'Aqeed revival';
    const Whatsapp = 'Whatsapp';
}
