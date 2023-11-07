<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TransactionTypeEnum extends Enum
{
    const ALL = 'All';
    const NEW_BUSINESS = 'New Business';
    const EXISTING_CUSTOMER_RENEWAL = 'Existing Customer Renewal';
    const EXISTING_CUSTOMER_NEW_BUSINESS = 'Existing Customer New Business';
    const ENDORSEMENT = 'Endorsement';
}
