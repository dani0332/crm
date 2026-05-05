<?php

namespace App\Enums;

enum ConversionOptimizationCapPercentageEnum: int
{
    use Enumable;

    case Ten = 10;
    case Twenty = 20;
    case Thirty = 30;

    /**
     * Human-readable percentage for filters (numeric, not spelt-out words).
     */
    public function label(): string
    {
        return sprintf('%d%%', $this->value);
    }
}
