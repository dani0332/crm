<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class QuoteTypeShortCode extends Enum
{
    const BIK = 'BIK';
    const BUS = 'BUS';
    const CAR = 'CAR';
    const HEA = 'HEA';
    const HOM = 'HOM';
    const LIF = 'LIF';
    const TRA = 'TRA';
    const YAC = 'YAC';
    const PET = 'PET';
    const CYC = 'CYC';
    const JSK = 'JSK';

    public static function getName($value)
    {
        $types = [
            1 => QuoteTypeShortCode::CAR,
            2 => QuoteTypeShortCode::HOM,
            3 => QuoteTypeShortCode::HEA,
            4 => QuoteTypeShortCode::LIF,
            5 => QuoteTypeShortCode::BUS,
            6 => QuoteTypeShortCode::BIK,
            7 => QuoteTypeShortCode::YAC,
            8 => QuoteTypeShortCode::TRA,
            9 => QuoteTypeShortCode::PET,
            10 => QuoteTypeShortCode::CYC,
            11 => QuoteTypeShortCode::JSK,
        ];

        return $types[$value] ?? 'Unknown';
    }
}
