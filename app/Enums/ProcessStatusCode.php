<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ProcessStatusCode extends Enum
{
    const PENDING = 'Pending';
    const COMPLETED = 'Completed';
    const PROCEED = 'Proceed';
    const IN_PROGRESS = 'In Progress';
    const UPLOADED = 'Uploaded';
    const FETCHING_PLANS = 'Fetching Plans';
    const PLANS_FETCHED = 'Plans Fetched';
}
