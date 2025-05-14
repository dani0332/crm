<?php

namespace App\Enums\Logger;

enum LoggerFeatureEnum: string
{
    case ALLOCATION = 'allocation';

    case ALLOCATION_AUDIT = 'allocation-audit';

    case OCR = 'ocr';
}
