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
    public const HOME_RENEWAL_AUTOMATED_FOLLOWUPS = 'home_renewal_automated_followups';
    public const WHATSAPP_NOTIFICATION_TO_CUSTOMER_NO_PLANS = 'whatsapp_notification_to_customer_no_plans';
    public const TRAVEL_ALLIANCE_FAILED_ALLOCATION = 'travel_alliance_failed_allocation';
    public const HOME_RENEWAL_OCB = 'home_renewal_ocb';
    public const CUSTOMER_NOTIFY_UNAVAILABLE_ADVIOSR = 'customer_notify_unavailable_advisor';
    public const INTRODUCTORY_EMAIL_TO_CUSTOMER = 'introductory_email_to_customer';
    public const MOTOR_PCP_FOLLOWUPS = 'motor_pcp_followups';
    public const MOTOR_PCP_OCB = 'motor_pcp_ocb';
    public const COMPANY_CAR_AUTOMATED_FOLLOWUPS = 'company_car_automated_followups';
    public const COMPANY_CAR_OCB = 'company_car_ocb';
    public const AIG_WORKFLOW = 'aig_workflow';
    public const LIFE_OCA_EMAIL = 'life_oca_email';
    public const CAR_COMMERCIAL_OCB = 'car_commercial_ocb';
    public const TRAVEL_AIG_WORKFLOW = 'travel_aig_workflow';
    public const LIFE_FIC_EMAIL = 'life_fic_email';
    public const LIFE_ADVANCE_BIRTHDAY_WISH_EMAIL = 'life_advance_birthday_wish_email';
    public const LIFE_BIRTHDAY_WISH_EMAIL = 'life_birthday_wish_email';
    public const LIFE_AUTOMATED_FOLLOWUPS = 'life_automated_followups';
    public const SIC_HEALTH_FOLLOWUPS_WA = 'sic_health_followups_wa';

    // BOR workflow types
    public const BOR_REQUEST = 'bor_request';
    public const BOR_CANCEL = 'bor_cancel';
    public const BOR_UPLOAD = 'bor_upload';
    public const BOR_INSURER_NOTIFICATION = 'bor_insurer_notification';
    public const BOR_STATUS_UPDATE = 'bor_status_update';
    public const CAR_AUTOMATION_FAILED = 'car_automation_failed';
    public const OE_ASSIGNMENT = 'oe_assignment';
    public const GOOGLE_REVIEW_EMAIL = 'google_review_email';
    public const CAR_CQF_RENEWAL_FOLLOWUPS = 'car_cqf_renewal_followups';
    public const CAR_CQF_RENEWAL_FOLLOWUPS = 'car_cqf_renewal_followups';
}
