<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class QuoteTypeId extends Enum
{
    const Car = 1;
    const Home = 2;
    const Health = 3;
    const Life = 4;
    const Business = 5;
    const Bike = 6;
    const Yacht = 7;
    const Travel = 8;
}
