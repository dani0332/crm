<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ManagementReportCategoriesEnum extends Enum
{
    const SALE_SUMMARY = 'Sales Summary';
    const SALE_DETAIL = 'Sales Detail';
    const ENDING_POLICIES = 'Ending Policies';
    const TRANSACTION = 'Transaction';
    const ACTIVE_POLICIES = 'Active Policies';
    const ENDORSEMENT = 'Endorsement';
    const INSTALLMENT = 'Installment';
}
