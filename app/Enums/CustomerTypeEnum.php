<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class CustomerTypeEnum extends Enum
{
    const Individual = 'Individual';
    const Entity = 'Entity';
    const Business = 'Business';
    const IndividualShort = 'IND';
    const EntityShort = 'ENT';
}
