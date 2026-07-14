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
    public const TRAVEL_QATAR_FAILED_ALLOCATION = 'travel_qatar_failed_allocation';
    public const CAR_NEW_POLICY = 'car_new_policy';
    public const COMMERCIAL_CAR_NEW_POLICY = 'commercial_car_new_policy';
    public const BIKE_NEW_POLICY = 'bike_new_policy';
    public const LIFE_NEW_POLICY = 'life_new_policy';
    public const TRAVEL_NEW_POLICY = 'travel_new_policy';
    public const CYCLE_NEW_POLICY = 'cycle_new_policy';
    public const YACHT_NEW_POLICY = 'yacht_new_policy';
    public const HOME_NEW_POLICY = 'home_new_policy';
    public const BUSINESS_NEW_POLICY = 'business_new_policy';
    public const PET_NEW_POLICY = 'pet_new_policy';
    public const HEALTH_NEW_POLICY = 'health_new_policy';
    public const PROFESSIONAL_NEW_POLICY = 'professional_new_policy';
    public const GROUP_MEDICAL_NEW_POLICY = 'group_medical_new_policy';
    public const CAR_FLEET_NEW_POLICY = 'car_fleet_new_policy';
    public const TRADE_NEW_POLICY = 'trade_new_policy';
    public const OTHER_BUSINESS_NEW_POLICY = 'other_business_new_policy';
    public const DEVICE_NEW_POLICY = 'device_new_policy';
    public const DEVICE_AUTOMATION_FAILED = 'device_automation_failed';
    public const DEVICE_UPDATE_POLICY = 'device_update_policy';
    public const CYBER_NEW_POLICY = 'cyber_new_policy';
    public const HOME_RENEWAL_OCB = 'home_renewal_ocb';
    public const CUSTOMER_NOTIFY_UNAVAILABLE_ADVIOSR = 'customer_notify_unavailable_advisor';
    public const INTRODUCTORY_EMAIL_TO_CUSTOMER = 'introductory_email_to_customer';
    public const SEND_POLICY_ISSUED_WHATSAPP_MESSAGE_TO_CUSTOMER = 'send_policy_issued_whatsapp_message_to_customer';
    public const SEND_POLICY_ISSUED_WHATSAPP_MESSAGE_TO_CUSTOMER_TRAVEL = 'send_policy_issued_whatsapp_message_to_customer_travel';
    public const MOTOR_PCP_FOLLOWUPS = 'motor_pcp_followups';
    public const MOTOR_PCP_OCB = 'motor_pcp_ocb';
    public const COMPANY_CAR_AUTOMATED_FOLLOWUPS = 'company_car_automated_followups';
    public const COMPANY_CAR_OCB = 'company_car_ocb';
    public const AIG_WORKFLOW = 'aig_workflow';
    public const LIFE_OCA_EMAIL = 'life_oca_email';
    public const SAVINGS_OCA_EMAIL = 'savings_oca_email';
    public const CAR_COMMERCIAL_OCB = 'car_commercial_ocb';
    public const TRAVEL_AIG_WORKFLOW = 'travel_aig_workflow';
    public const CAR_INTRO_EMAIL = 'car_intro_email';
    public const LIFE_FIC_EMAIL = 'life_fic_email';
    public const LIFE_ADVANCE_BIRTHDAY_WISH_EMAIL = 'life_advance_birthday_wish_email';
    public const LIFE_BIRTHDAY_WISH_EMAIL = 'life_birthday_wish_email';
    public const LIFE_AUTOMATED_FOLLOWUPS = 'life_automated_followups';
    public const SIC_HEALTH_FOLLOWUPS_WA = 'sic_health_followups_wa';
    public const CAR_CQF_RENEWALS_ERRORS = 'car_cqf_renewals_errors';

    // BOR workflow types
    public const BOR_REQUEST = 'bor_request';
    public const BOR_UPLOAD = 'bor_upload';
    public const BOR_INSURER_NOTIFICATION = 'bor_insurer_notification';
    public const CAR_AUTOMATION_FAILED = 'car_automation_failed';
    public const CYBER_AUTOMATION_FAILED = 'cyber_automation_failed';
    public const TRAVEL_POLICY_ISSUANCE_AUTOMATION_FAILED = 'travel_policy_issuance_automation_failed';
    public const OE_ASSIGNMENT = 'oe_assignment';
    public const CLAIM_INTRODUCTORY_EMAIL_TO_CUSTOMER = 'claim_introductory_email_to_customer';
    public const CLAIM_GOOGLE_REVIEW_EMAIL = 'claim_google_review_email';
    public const CLAIM_HEALTH_GOOGLE_REVIEW_EMAIL = 'claim_health_google_review_email';
    public const CLAIM_SUB_STATUS_CUSTOMER_NOTIFICATION = 'claim_sub_status_customer_notification';
    public const CAR_CQF_RENEWAL_FOLLOWUPS = 'car_cqf_renewal_followups';
    public const CAR_MISSING_DOC_REMINDER = 'car_missing_doc_reminder';
    public const TRAVEL_AUTOMATED_FOLLOWUPS = 'travel_automated_followups';
    public const TRAVEL_RENEWAL_AUTOMATED_FOLLOWUPS = 'travel_renewal_automated_followups';
    public const SEND_EP_ECB_POLICY_DOCUMENTS_EMAIL = 'send_ep_ecb_policy_documents_email';
    public const CAR_EP_RETARGETING_REMINDER = 'car_ep_retargeting_reminder';

    // Cyber workflow
    public const CYBER_OCB_INTRO_EMAIL = 'cyber_ocb_intro_email';
    public const CYBER_OCB_INTRO_WHATSAPP = 'sendOcbCyberWhatsapp';
    public const CYBER_AUTOMATED_FOLLOWUPS = 'cyber_automated_followups';

    // Health STP Advisor Notification
    public const HEALTH_STP_ADVISOR_NOTIFICATION = 'health_stp_advisor_notification';
    public const HEALTH_STP_ADVISOR_NOTIFICATION_API_FAILED = 'health_stp_advisor_notification_api_failed';

    // Device Workflow Types
    public const DEVICE_AUTOMATED_FOLLOWUPS = 'device_automated_followups';
    public const DEVICE_OCB_INTRO_EMAIL = 'device_ocb_intro_email';
    public const DEVICE_OCB_INTRO_WHATSAPP = 'device_ocb_intro_whatsapp';
    public const DEVICE_ZERO_PLANS_EMAIL = 'device_zero_plans_email';

    // Misreport Enum
    public const SEND_MISREPORT_EMAIL = 'send_misreport_email';
    public const SEND_FAILED_ILA_EMAILS = 'send_failed_ila_emails';
    public const MANAGER_DEACTIVATION_EMAIL = 'manager_deactivation_email';

    // Non-motor renewals
    public const CQF_NON_MOTOR_RENEWALS = 'cqf_non_motor_renewals';
    public const CAR_INTRO_EMAIL_WITHOUT_VEHICLE_DETAILS = 'car_intro_email_without_vehicle_details';

    // Motor Revival workflow
    public const MOTOR_REVIVAL_OCB = 'motor_revival_ocb';
    public const MOTOR_REVIVAL_FOLLOWUP = 'motor_revival_followup';

    // Life Revival OCB
    public const LIFE_REVIVAL_OCB = 'life_revival_ocb';
    public const AML_AUTOMATION_OUTCOME = 'aml_automation_outcome';

    // Home Revival OCB
    public const HOME_REVIVAL_OCB = 'home_revival_ocb';
    public const HOME_REVIVAL_FOLLOWUP = 'home_revival_followup';
    public const ADVISOR_PAYMENT_NOTIFICATION = 'advisor_payment_notification';
    public const INSTANT_CHAT_EXPORT = 'instant_chat_export';
    public const HIGH_RISK_NOTIFICATION = 'high-risk-notification';
}
