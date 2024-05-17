<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ManagementReportTypeEnum extends Enum
{
    const ISSUED_POLICIES = 'Issued Policies';
    const TRANSACTION_PAYMENTS = 'Transaction Payments';
    const EXPIRING_POLICIES = 'Expiring Policies';
    const ACTIVE_POLICIES = 'Active Policies';
}
