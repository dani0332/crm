<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class GroupMedicalRegionEnum extends Enum
{
    public const AUH = 'auh';
    public const NON_AUH = 'non-auh';

    /**
     * @return array<int, string>
     */
    public static function regionKeys(): array
    {
        return [self::AUH, self::NON_AUH];
    }
}
