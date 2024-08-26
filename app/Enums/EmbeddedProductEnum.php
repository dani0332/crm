<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EmbeddedProductEnum extends Enum
{
    const AP1 = 'Silver';
    const AP2 = 'Gold';
    const AP3 = 'Platinum';
    const TRAVEL = 'TRA';
    const COURIER = 'COU';

    // used in report for source
    const SRC_CAR_EMBEDDED_PRODUCT = 'CAR EMBEDDED PRODUCT';

    public static function getAlfredProtectCodes(): array
    {
        return [
            'AP1',
            'AP2',
            'AP3',
        ];
    }
}
