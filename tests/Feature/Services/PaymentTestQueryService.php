<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Payment;
use App\Models\PaymentSplits;

class PaymentTestQueryService
{
    public function getPaymentByQuoteCode(string $quoteCode): ?Payment
    {
        return Payment::where('code', $quoteCode)->first();
    }

    public function getPaymentSplitByCodeAndSerial(string $paymentCode, int $srNo = 1): ?PaymentSplits
    {
        return PaymentSplits::where('code', $paymentCode)
            ->where('sr_no', $srNo)
            ->first();
    }
}

