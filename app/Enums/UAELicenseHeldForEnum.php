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
