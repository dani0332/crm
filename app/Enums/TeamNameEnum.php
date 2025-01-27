<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TeamNameEnum extends Enum
{
    public const CAR = 'Car';
    public const HOME = 'Home';
    public const CYCLE = 'Cycle';
    public const HEALTH = 'Health';
    public const CORPLINE = 'Corpline';
    public const PET = 'Pet';
    public const YACHT = 'Yacht';
    public const RENEWALS = 'Renewals';
    public const AFFINITY = 'Affinity';
    public const ORGANIC = 'Organic';
    public const PCP = 'PCP';
    public const EBP = 'Entry-Level';
    public const RM_NB = 'Best';
    public const RM_SPEED = 'Good';
    public const MOTOR_CORPORATE_NB_COMMERCIAL = 'Motor Corporate - NB Commercial';
    public const BDM = 'BDM';
    public const SBDM = 'SBDM';
    public const MOTOR_COOPERATE_RENEWALS = 'Motor cooperate Renewals';
    public const RM_RENEWALS = 'RM - Renewals';
    public const CORPLINE_TEAM = 'Corpline - Team';
    public const CORPLINE_RENEWALS = 'Corpline - Renewals';
    public const HOME_RENEWALS = 'Home - Renewals';
    public const PET_TEAM = 'Pet - Team';
    public const PET_RENEWALS = 'Pet - Renewals';
    public const YACHT_TEAM = 'Yacht - Team';
    public const YACHT_RENEWALS = 'Yacht - Renewals';
    public const CYCLE_RENEWALS = 'Cycle - Renewals';
    public const SIC_UNASSISTED = 'SIC 2.0 Unassisted';
    public const VALUE = 'Value';
    public const VOLUME = 'Volume';
    public const AMT = 'AMT';
    public const MICRO_SME = 'Micro SME';
    public const CORPLINE_NEW = 'Corpline - Team';
    public const BIKE = 'Bike';
    public const Bike_Team = 'Bike - Team';
    public const TRAVEL_RENEWALS = 'Travel - Renewals';
    public const TRAVEL_TEAM = 'Travel - Team';

    public static function getTeamID(string $teamName)
    {
        $teamIDs = [
            self::CAR => 2,
        ];

        return $teamIDs[$teamName] ?? null;
    }
}
