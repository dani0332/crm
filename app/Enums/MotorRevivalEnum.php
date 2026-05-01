<?php

namespace App\Enums;

use Illuminate\Support\Str;

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

    // MACRM voucher values
    case CTA_TEXT = 'View Car Quotes';

    public const ILA_HIGH_INTENT_WAIT_MINUTES = 15;
    public const ILA_MEDIUM_INTENT_WAIT_HOURS = 3;

    public static function getEngagementLevelLabel(?string $storedValue): string
    {
        if (blank($storedValue)) {
            return '';
        }

        return match ($storedValue) {
            self::COMMS_TRIGGERED->value => 'Revival Comms Triggered',
            self::INTENT_LOW->value => 'Revival Intent Low',
            self::MEDIUM_INTENT->value => 'Revival Intent Medium',
            self::INTENT_HIGH->value => 'Revival Intent High',
            default => Str::headline(str_replace(['_', '-'], ' ', $storedValue)),
        };
    }
}
