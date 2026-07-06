<?php

namespace App\Enums;

enum EmirateTypeEnum: int
{
    case DUBAI = 0;
    case NORTHERN = 1;
    case DUBAI_AND_NORTHERN_BOTH = 2;
    case ABU_DHABI = 3;

    public function label(): string
    {
        return match ($this) {
            self::DUBAI => 'Dubai',
            self::NORTHERN => 'Northern',
            self::DUBAI_AND_NORTHERN_BOTH => 'Dubai and northern both',
            self::ABU_DHABI => 'Abu Dhabi',
        };
    }

    public static function labels(): array
    {
        return array_map(fn ($case) => $case->label(), self::cases());
    }

    public static function fromText(string $value): ?self
    {
        $normalized = strtoupper(str_replace(' ', '_', $value));

        return collect(self::cases())
            ->first(fn ($case) => $case->name === $normalized);
    }
}
