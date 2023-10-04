<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentTooltip extends Enum
{
    const COLLECTION_DATE = 'The date when the payment is due or when it was collected. Ensure to update this date accurately to maintain proper payment records.';
}
