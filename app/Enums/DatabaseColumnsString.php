<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class DatabaseColumnsString extends Enum
{
    const quoteStatus= 'quote_status_id';
    const quotemobile= 'mobile_no';
    const quoteemail= 'email';
}
