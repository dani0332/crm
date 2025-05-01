<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AmlAutomationStatus extends Enum
{
    const QUEUE_STATUS = 'queue';
    const PROCESSING_STATUS = 'processing';
    const COMPLETE_STATUS = 'complete';
    const FAILED_STATUS = 'failed';
}
