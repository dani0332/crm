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

    // used in report for source
    const SRC_CAR_EMBEDDED_PRODUCT = 'CAR EMBEDDED PRODUCT';
}
