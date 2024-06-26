<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ReportsLeadTypeEnum extends Enum
{
    public const NEW_LEADS = 'new_leads';
    public const NOT_INTERESTED = 'not_interested';
    public const IN_PROGRESS = 'in_progress';
    public const CANCELLED_LEADS = 'cancelled_leads';
    public const MANUAL_CREATED = 'manual_created';
    public const BAD_LEAD = 'bad_leads';
    public const AFIA_RENEWALS_COUNT = 'afia_renewals_count';
    public const SALE_LEAD = 'sale_leads';
    public const CREATED_SALE_LEAD = 'created_sale_leads';
    public const OTHERS = 'others';
    public const TOTAL_LEADS = 'total_leads';
}
