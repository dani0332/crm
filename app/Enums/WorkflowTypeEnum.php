<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class WorkflowTypeEnum extends Enum
{
    public const RENEWALS = 'RENEWALS';
    public const NEW_BUSINESS = 'NEW_BUSINESS';
    public const TRAVEL_HAPEX_EMAIL_REMINDER = 'travel_hapex';
    public const TRAVEL_HAPEX_STOP_EMAIL_REMINDER = 'travel_hapex_disable';
    public const HEALTH_SIC_FOLLOWUPS = 'health_sic_followups';
    public const TRAVEL_SIC_FOLLOWUPS = 'travel_sic_followups';
    public const CAR_DEDICATED_SIC_FOLLOWUPS = 'car_dedicated_sic_followups';
}
