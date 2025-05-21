<?php

namespace App\Enums\Logger;

enum LoggerFeatureEnum: string
{
    case ALLOCATION = 'allocation';

    case ALLOCATION_AUDIT = 'allocation-audit';

    case FTC_EMAIL = 'ftc-email';

    case TRAVEL_RENEWALS = 'travel-renewals';
    case PCP_CLIENT = 'private-client';
}
