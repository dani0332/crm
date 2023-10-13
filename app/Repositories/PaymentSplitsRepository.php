<?php

namespace App\Repositories;

use App\Models\PaymentSplits;

class PaymentSplitsRepository
{
    public static function getByCode($code)
    {
        return PaymentSplits::with(['paymentStatus', 'paymentMethod'])
            ->where('code', $code)
            ->get();
    }
}

