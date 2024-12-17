<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ManagementReportTypeEnum extends Enum
{
    const ISSUED_POLICIES = 'Issued Policies';
    const APPROVED_TRANSACTIONS = 'Approved Transactions';
    const EXPIRING_POLICIES = 'Expiring Policies';
    const ACTIVE_POLICIES = 'Active Policies';
    const BOOKED_POLICIES = 'Booked Policies';
    const PAID_TRANSACTIONS = 'Paid Transactions';
}
