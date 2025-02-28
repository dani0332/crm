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
    case SU_CAR_UPDATE = 8;
    case SU_TRAVEL_UPDATE = 9;
    case SU_BIKE_UPDATE = 10;
    case SU_CYCLE_UPDATE = 11;
    case SU_YACHT_UPDATE = 12;
    case SU_HOME_UPDATE = 13;
    case SU_LIFE_UPDATE = 14;
    case SU_BUSINESS_UPDATE = 15;
    case CAR_NEW_POLICY = 16;
    case BIKE_NEW_POLICY = 17;
    case LIFE_NEW_POLICY = 18;
    case TRAVEL_NEW_POLICY = 19;
    case CYCLE_NEW_POLICY = 20;
    case YACHT_NEW_POLICY = 21;
    case HOME_NEW_POLICY = 22;
    case BUSINESS_NEW_POLICY = 23;
    case SU_PET_UPDATE = 24;
    case PET_NEW_POLICY = 25;

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
            QuoteFlowType::SU_CAR_UPDATE => 'su_car_update',
            QuoteFlowType::SU_TRAVEL_UPDATE => 'su_travel_update',
            QuoteFlowType::SU_BIKE_UPDATE => 'su_bike_update',
            QuoteFlowType::SU_CYCLE_UPDATE => 'su_cycle_update',
            QuoteFlowType::SU_YACHT_UPDATE => 'su_yacht_update',
            QuoteFlowType::SU_HOME_UPDATE => 'su_home_update',
            QuoteFlowType::SU_LIFE_UPDATE => 'su_life_update',
            QuoteFlowType::SU_BUSINESS_UPDATE => 'su_business_update',
            QuoteFlowType::CAR_NEW_POLICY => 'car_new_policy',
            QuoteFlowType::BIKE_NEW_POLICY => 'bike_new_policy',
            QuoteFlowType::LIFE_NEW_POLICY => 'life_new_policy',
            QuoteFlowType::TRAVEL_NEW_POLICY => 'travel_new_policy',
            QuoteFlowType::CYCLE_NEW_POLICY => 'cycle_new_policy',
            QuoteFlowType::YACHT_NEW_POLICY => 'yacht_new_policy',
            QuoteFlowType::HOME_NEW_POLICY => 'home_new_policy',
            QuoteFlowType::BUSINESS_NEW_POLICY => 'business_new_policy',
            QuoteFlowType::SU_PET_UPDATE => 'su_pet_update',
            QuoteFlowType::PET_NEW_POLICY => 'pet_new_policy',
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
            8 => QuoteFlowType::SU_CAR_UPDATE,
            9 => QuoteFlowType::SU_TRAVEL_UPDATE,
            10 => QuoteFlowType::SU_BIKE_UPDATE,
            11 => QuoteFlowType::SU_CYCLE_UPDATE,
            12 => QuoteFlowType::SU_YACHT_UPDATE,
            13 => QuoteFlowType::SU_HOME_UPDATE,
            14 => QuoteFlowType::SU_LIFE_UPDATE,
            15 => QuoteFlowType::SU_BUSINESS_UPDATE,
            16 => QuoteFlowType::CAR_NEW_POLICY,
            17 => QuoteFlowType::BIKE_NEW_POLICY,
            18 => QuoteFlowType::LIFE_NEW_POLICY,
            19 => QuoteFlowType::TRAVEL_NEW_POLICY,
            20 => QuoteFlowType::CYCLE_NEW_POLICY,
            21 => QuoteFlowType::YACHT_NEW_POLICY,
            22 => QuoteFlowType::HOME_NEW_POLICY,
            23 => QuoteFlowType::BUSINESS_NEW_POLICY,
            24 => QuoteFlowType::SU_PET_UPDATE,
            25 => QuoteFlowType::PET_NEW_POLICY,
            default => null,  // Return null if the value doesn't match any case
        };
    }
}
