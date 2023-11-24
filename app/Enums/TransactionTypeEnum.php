<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TransactionTypeEnum extends Enum
{
    const ALL = 'All';
    const NEW_BUSINESS = 'New Business';
    const EXISTING_CUSTOMER_RENEWAL = "Existing Customer's Renewal";
    const EXISTING_CUSTOMER_NEW_BUSINESS = "Existing Customer's New Business";
    const ENDORSEMENT = 'Endorsement';
}
