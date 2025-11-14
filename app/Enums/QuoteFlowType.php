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
    case CAR_NEW_POLICY = 16;
    case BIKE_NEW_POLICY = 17;
    case LIFE_NEW_POLICY = 18;
    case TRAVEL_NEW_POLICY = 19;
    case CYCLE_NEW_POLICY = 20;
    case YACHT_NEW_POLICY = 21;
    case HOME_NEW_POLICY = 22;
    case BUSINESS_NEW_POLICY = 23;
    case PET_NEW_POLICY = 25;
    case HEALTH_NEW_POLICY = 27;
    case GROUP_MEDICAL_NEW_POLICY = 29;
    case CAR_FLEET_NEW_POLICY = 31;
    case TRADE_NEW_POLICY = 33;
    case OTHER_BUSINESS_NEW_POLICY = 35;
    case HOME_AUTOMATED_FOLLOWUPS = 8;
    case MOTOR_PCP_FOLLOWUPS = 9;
    case CAR_CQF_RENEWAL_FOLLOWUPS = 11;
    case LIFE_AUTOMATED_FOLLOWUPS = 12;
    case LIFE_ADVANCE_BIRTHDAY_WISH = 13;
    case LIFE_BIRTHDAY_WISH = 14;
    case HOME_RENEWAL_AUTOMATED_FOLLOWUPS = 15;
    case SIC_HEALTH_FOLLOWUPS_WA = 10;
    case CAR_AUTOMATION_FAILED = 36;
    case CAR_AI_ADVISOR_OCB = 39;

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
            QuoteFlowType::CAR_NEW_POLICY => 'car_new_policy',
            QuoteFlowType::BIKE_NEW_POLICY => 'bike_new_policy',
            QuoteFlowType::LIFE_NEW_POLICY => 'life_new_policy',
            QuoteFlowType::TRAVEL_NEW_POLICY => 'travel_new_policy',
            QuoteFlowType::CYCLE_NEW_POLICY => 'cycle_new_policy',
            QuoteFlowType::YACHT_NEW_POLICY => 'yacht_new_policy',
            QuoteFlowType::HOME_NEW_POLICY => 'home_new_policy',
            QuoteFlowType::BUSINESS_NEW_POLICY => 'business_new_policy',
            QuoteFlowType::PET_NEW_POLICY => 'pet_new_policy',
            QuoteFlowType::HEALTH_NEW_POLICY => 'health_new_policy',
            QuoteFlowType::GROUP_MEDICAL_NEW_POLICY => 'group_medical_new_policy',
            QuoteFlowType::CAR_FLEET_NEW_POLICY => 'car_fleet_new_policy',
            QuoteFlowType::TRADE_NEW_POLICY => 'trade_new_policy',
            QuoteFlowType::OTHER_BUSINESS_NEW_POLICY => 'other_business_new_policy',
            QuoteFlowType::HOME_AUTOMATED_FOLLOWUPS => 'home_automated_followups',
            QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS => 'home_renewal_automated_followups',
            QuoteFlowType::MOTOR_PCP_FOLLOWUPS => 'motor_pcp_followups',
            QuoteFlowType::SIC_HEALTH_FOLLOWUPS_WA => 'sic_health_followups_wa',
            QuoteFlowType::LIFE_AUTOMATED_FOLLOWUPS => 'life_automated_followups',
            QuoteFlowType::LIFE_ADVANCE_BIRTHDAY_WISH => 'life_advance_birthday_wish',
            QuoteFlowType::LIFE_BIRTHDAY_WISH => 'life_birthday_wish',
            QuoteFlowType::CAR_AUTOMATION_FAILED => 'car_automation_failed',
            QuoteFlowType::CAR_AI_ADVISOR_OCB => 'car_ai_advisor_ocb',
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
            16 => QuoteFlowType::CAR_NEW_POLICY,
            17 => QuoteFlowType::BIKE_NEW_POLICY,
            18 => QuoteFlowType::LIFE_NEW_POLICY,
            19 => QuoteFlowType::TRAVEL_NEW_POLICY,
            20 => QuoteFlowType::CYCLE_NEW_POLICY,
            21 => QuoteFlowType::YACHT_NEW_POLICY,
            22 => QuoteFlowType::HOME_NEW_POLICY,
            23 => QuoteFlowType::BUSINESS_NEW_POLICY,
            25 => QuoteFlowType::PET_NEW_POLICY,
            27 => QuoteFlowType::HEALTH_NEW_POLICY,
            29 => QuoteFlowType::GROUP_MEDICAL_NEW_POLICY,
            31 => QuoteFlowType::CAR_FLEET_NEW_POLICY,
            33 => QuoteFlowType::TRADE_NEW_POLICY,
            35 => QuoteFlowType::OTHER_BUSINESS_NEW_POLICY,
            8 => QuoteFlowType::HOME_AUTOMATED_FOLLOWUPS,
            9 => QuoteFlowType::MOTOR_PCP_FOLLOWUPS,
            10 => QuoteFlowType::SIC_HEALTH_FOLLOWUPS_WA,
            11 => QuoteFlowType::CAR_CQF_RENEWAL_FOLLOWUPS,
            12 => QuoteFlowType::LIFE_AUTOMATED_FOLLOWUPS,
            13 => QuoteFlowType::LIFE_ADVANCE_BIRTHDAY_WISH,
            14 => QuoteFlowType::LIFE_BIRTHDAY_WISH,
            15 => QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS,
            36 => QuoteFlowType::CAR_AUTOMATION_FAILED,
            39 => QuoteFlowType::CAR_AI_ADVISOR_OCB,
            default => null,  // Return null if the value doesn't match any case
        };
    }
}
