<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class CarRoadsideAssistanceEnum extends Enum
{
    public const RSA = '800 462 372'; // RSA Insurance (LIVA)
    public const OIC = '800 6565'; // Oman Insurance
    public const ADNIC = '800 4405'; // ADNIC Insurance
    public const AXA = '800 292'; // GIG Insurance
    public const TM = '600 50 8181'; // Tokio Marine Insurance
    public const QIC = '600 50 8181'; // Qatar Insurance
    public const NTPJSC = '800 4101'; // Noor Takaful General
    public const NTFPJSC = '800 4101'; // Noor Takaful Family
    public const OI = '600 575751'; // Oriental Insurance
    public const SI = '800 725 262'; // Salama Insurance
    public const DNIRC = '800 4101'; // Dubai National Insurance
}
