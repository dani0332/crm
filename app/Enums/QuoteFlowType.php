<?php

namespace App\Enums;

enum QuoteFlowType: int
{
    case HEALTH_AUTOMATED_FOLLOWUPS = 1;
    case HEALTH_SIC_FOLLOWUPS = 2;
    case MOTOR_SIC_FOLLOWUPS = 3;
    case MOTOR_AUTOMATED_FOLLOWUPS = 4;
    case TRAVEL_SIC_FOLLOWUPS = 5;
    case TRAVEL_AUTOMATED_FOLLOWUPS = 6;
    case NEW_BUSINESS_MOTOR_AUTOMATED_FOLLOWUPS = 7;
    case HOME_AUTOMATED_FOLLOWUPS = 8;
    case MOTOR_PCP_FOLLOWUPS = 9;
    case CAR_CQF_RENEWAL_FOLLOWUPS = 11;
    case LIFE_AUTOMATED_FOLLOWUPS = 12;
    case LIFE_ADVANCE_BIRTHDAY_WISH = 13;
    case LIFE_BIRTHDAY_WISH = 14;
    case HOME_RENEWAL_AUTOMATED_FOLLOWUPS = 15;
    case SIC_HEALTH_FOLLOWUPS_WA = 10;
    case CAR_CQF_RENEWAL_FOLLOWUPS = 11;

    public function label(): string
    {
        return match ($this) {
            QuoteFlowType::HEALTH_AUTOMATED_FOLLOWUPS => 'health_automated_followups',
            QuoteFlowType::HEALTH_SIC_FOLLOWUPS => 'health_sic_followups',
            QuoteFlowType::MOTOR_SIC_FOLLOWUPS => 'motor_sic_followups',
            QuoteFlowType::MOTOR_AUTOMATED_FOLLOWUPS => 'motor_automated_followups',
            QuoteFlowType::TRAVEL_SIC_FOLLOWUPS => 'travel_sic_followups',
            QuoteFlowType::TRAVEL_AUTOMATED_FOLLOWUPS => 'travel_automated_followups',
            QuoteFlowType::NEW_BUSINESS_MOTOR_AUTOMATED_FOLLOWUPS => 'nb_motor_automated_followups',
            QuoteFlowType::HOME_AUTOMATED_FOLLOWUPS => 'home_automated_followups',
            QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS => 'home_renewal_automated_followups',
            QuoteFlowType::MOTOR_PCP_FOLLOWUPS => 'motor_pcp_followups',
            QuoteFlowType::CAR_CQF_RENEWAL_FOLLOWUPS => 'car_cqf_renewal_followups',
            QuoteFlowType::SIC_HEALTH_FOLLOWUPS_WA => 'sic_health_followups_wa',
            QuoteFlowType::CAR_CQF_RENEWAL_FOLLOWUPS => 'car_cqf_renewal_followups',
        };
    }

    public static function fromValue(int $value): ?self
    {
        return match ($value) {
            1 => QuoteFlowType::HEALTH_AUTOMATED_FOLLOWUPS,
            2 => QuoteFlowType::HEALTH_SIC_FOLLOWUPS,
            3 => QuoteFlowType::MOTOR_SIC_FOLLOWUPS,
            4 => QuoteFlowType::MOTOR_AUTOMATED_FOLLOWUPS,
            5 => QuoteFlowType::TRAVEL_SIC_FOLLOWUPS,
            6 => QuoteFlowType::TRAVEL_AUTOMATED_FOLLOWUPS,
            7 => QuoteFlowType::NEW_BUSINESS_MOTOR_AUTOMATED_FOLLOWUPS,
            8 => QuoteFlowType::HOME_AUTOMATED_FOLLOWUPS,
            9 => QuoteFlowType::MOTOR_PCP_FOLLOWUPS,
            11 => QuoteFlowType::CAR_CQF_RENEWAL_FOLLOWUPS,
            10 => QuoteFlowType::SIC_HEALTH_FOLLOWUPS_WA,
            12 => QuoteFlowType::LIFE_AUTOMATED_FOLLOWUPS,
            13 => QuoteFlowType::LIFE_ADVANCE_BIRTHDAY_WISH,
            14 => QuoteFlowType::LIFE_BIRTHDAY_WISH,
            15 => QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS,
            11 => QuoteFlowType::CAR_CQF_RENEWAL_FOLLOWUPS,
            default => null,  // Return null if the value doesn't match any case
        };
    }
}
