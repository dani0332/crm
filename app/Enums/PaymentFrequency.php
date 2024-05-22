<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentFrequency extends Enum
{
    const UPFRONT = 'upfront';
    const SPLIT_PAYMENTS = 'split_payments';
    const PAID = 'paid';
    const SEMI_ANNUAL = 'semi_annual';
    const QUARTERLY = 'quarterly';
    const MONTHLY = 'monthly';
    const CUSTOM = 'custom';
}
