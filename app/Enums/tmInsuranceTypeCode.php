<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class tmInsuranceTypeCode extends Enum
{
    const Car = 'Car';
    const Home = 'Home';
    const Health = 'Health';
    const Life = 'Life';
    const Business = 'Business';
    const Bike = 'Bike';
    const Yacht = 'Yacht';
    const Travel = 'Travel';
    const Critical = 'Critical';
}
