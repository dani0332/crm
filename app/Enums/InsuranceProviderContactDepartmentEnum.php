<?php

declare(strict_types=1);

namespace App\Enums;

enum InsuranceProviderContactDepartmentEnum: string
{
    case CLAIM = 'CLAIM';

    public static function labels(): array
    {
        return [
            self::CLAIM->value => 'Claims',
        ];
    }
}
