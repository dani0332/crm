<?php

namespace App\Enums\PDMigrations;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PDDealStatus extends Enum
{
    const WON = 'Won';
    const LOST = 'Lost';
    const OPEN = 'Open';
}
