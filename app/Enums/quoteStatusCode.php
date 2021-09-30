<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class quoteStatusCode extends Enum
{
    const completed = "completed";
    const pending = "pending";
    const rejected = "rejected";
    const approved = "approved";
    const approvalRequired = "approvalRequired";
    const AMLScreeningCleared = "AMLScreeningCleared";
    const AMLScreeningFailed = "AMLScreeningFailed";
}
