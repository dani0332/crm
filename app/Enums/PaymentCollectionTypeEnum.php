<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentCollectionTypeEnum extends Enum
{
    const BROKER = 'broker';
    const INSURER = 'insurer';
}
