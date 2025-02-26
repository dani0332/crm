<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EmbeddedProductTypeEnum extends Enum
{
    const INSURANCE = 'insurance';
    const NON_INSURANCE = 'non-insurance';
}
