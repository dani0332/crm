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
    public const HEALTH_SIC_FOLLOWUPS = 'health_sic_followups';
    public const TRAVEL_SIC_FOLLOWUPS = 'travel_sic_followups';
    public const NEW_BUSINESS_MOTOR_AUTOMATED_FOLLOWUPS = 'nb_motor_automated_followups';
    public const NEW_BUSINESS_MOTOR_EVENT_FOLLOWUPS = 'nb_motor_event_followups';
    public const UNSUBSCRIBE_REQUESTED_NOTIFICATIION = 'unsubscribe_requested_notification';
    public const HEALTH_APPLICATION_SUBMITTED = 'health_application_submitted';
    public const TRAVEL_RENEWALS_OCB = 'travel_renewals_ocb';
    public const HOME_AUTOMATED_FOLLOWUPS = 'home_automated_followups';
    public const WHATSAPP_NOTIFICATION_TO_CUSTOMER_NO_PLANS = 'whatsapp_notification_to_customer_no_plans';
    public const TRAVEL_ALLIANCE_FAILED_ALLOCATION = 'travel_alliance_failed_allocation';
}
