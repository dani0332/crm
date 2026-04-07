<?php

namespace App\Enums;

enum MotorRevivalEnum: string
{
    // engagement triggered cases
    case COMMS_TRIGGERED = 'Revival_Comms_Triggered';
    case INTENT_LOW = 'Revival_Intent_Low';
    case MEDIUM_INTENT = 'Revival_MediumIntent';
    case INTENT_HIGH = 'Revival_Intent_High';

    // CTA button clicks cases
    case BUY_NOW = 'BUY_NOW';
    case MYALFRED_LINK = 'MYALFRED_LINK';
    case VIEW_PLANS = 'VIEW_PLANS';
    case SEE_ALL_QUOTES = 'SEE_ALL_QUOTES';
    case VIEW_ALL_PLANS = 'VIEW_ALL_PLANS';
    case CAR_INSURANCE_PLAN = 'CAR_INSURANCE_PLAN';

    // Event channels cases
    case EMAIL = 'Email';
    case WHATSAPP = 'WhatsApp';
    case MY_ALFRED = 'myAlfred';
}
