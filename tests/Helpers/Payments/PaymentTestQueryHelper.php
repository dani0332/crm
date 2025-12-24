<?php

namespace Tests\Helpers\Payments;

use App\Models\Payment;
use App\Models\PaymentSplits;

/**
 * Helper class for querying payment-related entities in tests.
 * Handles retrieval of payments and payment splits from the database.
 */
class PaymentTestQueryHelper
{
    /**
     * Retrieve payment from database by quote code.
     *
     * @param string $quoteCode The quote code to search for
     * @return Payment|null
     */
    public static function getPaymentByQuoteCode(string $quoteCode): ?Payment
    {
        return Payment::on('sqlite')
            ->where('code', $quoteCode)
            ->first();
    }

    /**
     * Retrieve payment split from database by payment code and serial number.
     *
     * @param string $paymentCode The payment code
     * @param int $srNo The serial number
     * @return PaymentSplits|null
     */
    public static function getPaymentSplitByCodeAndSerial(string $paymentCode, int $srNo = 1): ?PaymentSplits
    {
        return PaymentSplits::on('sqlite')
            ->where('code', $paymentCode)
            ->where('sr_no', $srNo)
            ->first();
    }
}

