<?php

declare(strict_types=1);

namespace App\Enums;

enum MotorRevivalVoucherCode: string
{
    use Enumable;

    case TrialSevenDay = 'MA_FREE7_';

    public function codeForQuoteUuid(string $quoteUuid): string
    {
        return $this->value.strtoupper($quoteUuid);
    }
}
