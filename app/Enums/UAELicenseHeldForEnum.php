<?php

namespace App\Enums;


enum UAELicenseHeldForEnum: int
{
    case LESS_THAN_6_MONTHS = 1;
    case LESS_THAN_1_YEAR   = 2;
    case ONE_YEAR           = 3;
    case TWO_YEARS          = 4;
    case THREE_YEARS        = 5;
    case FOUR_YEARS         = 6;
    case FIVE_YEARS         = 7;
    case SIX_YEARS          = 8;
    case SEVEN_YEARS        = 9;
    case EIGHT_YEARS        = 10;
    case NINE_YEARS         = 11;
    case TEN_YEARS          = 12;
    case ELEVEN_YEARS       = 13;
    case TWELVE_YEARS       = 14;
    case THIRTEEN_YEARS     = 15;
    case FOURTEEN_YEARS     = 16;
    case FIFTEEN_YEARS      = 17;
    case SIXTEEN_YEARS      = 18;
    case SEVENTEEN_YEARS    = 19;
    case EIGHTEEN_YEARS     = 20;
    case NINETEEN_YEARS     = 21;
    case TWENTY_YEARS_PLUS  = 22;

    public function code(): string
    {
        return match($this) {
            self::LESS_THAN_6_MONTHS => 'less than 6 months',
            self::LESS_THAN_1_YEAR   => 'less than 1 year',
            self::ONE_YEAR           => '1 year',
            self::TWO_YEARS          => '2 years',
            self::THREE_YEARS        => '3 years',
            self::FOUR_YEARS         => '4 years',
            self::FIVE_YEARS         => '5 years',
            self::SIX_YEARS          => '6 years',
            self::SEVEN_YEARS        => '7 years',
            self::EIGHT_YEARS        => '8 years',
            self::NINE_YEARS         => '9 years',
            self::TEN_YEARS          => '10 years',
            self::ELEVEN_YEARS       => '11 years',
            self::TWELVE_YEARS       => '12 years',
            self::THIRTEEN_YEARS     => '13 years',
            self::FOURTEEN_YEARS     => '14 years',
            self::FIFTEEN_YEARS      => '15 years',
            self::SIXTEEN_YEARS      => '16 years',
            self::SEVENTEEN_YEARS    => '17 years',
            self::EIGHTEEN_YEARS     => '18 years',
            self::NINETEEN_YEARS     => '19 years',
            self::TWENTY_YEARS_PLUS  => '20 years +',
        };
    }

    public function text(): string
    {
        return match($this) {
            self::LESS_THAN_6_MONTHS => '0 to 6 months',
            self::LESS_THAN_1_YEAR   => '6 to 12 months',
            self::ONE_YEAR           => '1 year',
            self::TWO_YEARS          => '2 years',
            self::THREE_YEARS        => '3 years and above',
            self::FOUR_YEARS         => '4 years',
            self::FIVE_YEARS         => '5 years and above',
            self::SIX_YEARS          => '6 years',
            self::SEVEN_YEARS        => '7 years',
            self::EIGHT_YEARS        => '8 years',
            self::NINE_YEARS         => '9 years',
            self::TEN_YEARS          => '10 years',
            self::ELEVEN_YEARS       => '11 years',
            self::TWELVE_YEARS       => '12 years',
            self::THIRTEEN_YEARS     => '13 years',
            self::FOURTEEN_YEARS     => '14 years',
            self::FIFTEEN_YEARS      => '15 years',
            self::SIXTEEN_YEARS      => '16 years',
            self::SEVENTEEN_YEARS    => '17 years',
            self::EIGHTEEN_YEARS     => '18 years',
            self::NINETEEN_YEARS     => '19 years',
            self::TWENTY_YEARS_PLUS  => '20 years +',
        };
    }
    /**
     * Get the UAELicenseHeldForEnum case by the database ID value.
     *
     * @param int|string|null $id
     * @return self|null
     */
    public static function fromId(int|string|null $id): ?self
    {
        if ($id === null) {
            return null;
        }

        // The enum values are assumed to match the DB 'id' field.
        foreach (self::cases() as $case) {
            if ((string)$case->value === (string)$id) {
                return $case;
            }
        }

        return null;
    }
}
