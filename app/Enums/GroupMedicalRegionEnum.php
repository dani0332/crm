<?php

declare(strict_types=1);

namespace App\Enums;

enum GroupMedicalRegionEnum: string
{
    case AUH = 'auh';
    case NON_AUH = 'non-auh';

    /**
     * @return list<string> keys for the region config in the allocation configuration
     */
    public static function regionKeys(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
