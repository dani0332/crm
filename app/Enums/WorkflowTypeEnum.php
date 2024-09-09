<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class WorkflowTypeEnum extends Enum
{
    public const RENEWALS = 'RENEWALS';
    public const NEW_BUSINESS = 'NEW_BUSINESS';
    public const TRAVEL_HAPEX_EMAIL_REMINDER = 'travel_hapex';
    public const TRAVEL_HAPEX_STOP_EMAIL_REMINDER = 'travel_hapex_disable';
    public const HEALTH_AUTOMATED_FOLLOWUPS = 'health_automated_followups';
    
}
