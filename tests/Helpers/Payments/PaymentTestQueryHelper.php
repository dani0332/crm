<?php

namespace Tests\Helpers\Payments;

use App\Models\Payment;
use App\Models\PaymentSplits;

/**
 * Payment test query helper.
 */
class PaymentTestQueryHelper
{
    /**
     * Retrieve payment from database by quote code.
     */
    public static function getPaymentByQuoteCode(string $quoteCode): ?Payment
    {
        return Payment::where('code', $quoteCode)
            ->first();
    }

    /**
     * Retrieve payment split from database by payment code and serial number.
     * Uses the default database connection (SQLite in tests as configured in phpunit.xml).
     */
    public static function getPaymentSplitByCodeAndSerial(string $paymentCode, int $srNo = 1): ?PaymentSplits
    {
        return PaymentSplits::where('code', $paymentCode)
            ->where('sr_no', $srNo)
            ->first();
    }
}
