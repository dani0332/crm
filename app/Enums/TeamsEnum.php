<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamsEnum: string
{
    case CAR = 'Car';
    case HOME = 'Home';
    case CYCLE = 'Cycle';
    case HEALTH = 'Health';
    case LIFE = 'Life';
    case CORPLINE = 'CorpLine';
    case GROUP_MEDICAL = 'Group Medical';
    case PET = 'Pet';
    case YACHT = 'Yacht';
    case RENEWALS = 'Renewals';
    case AFFINITY = 'Affinity';
    case ORGANIC = 'Organic';
    case PCP = 'PCP';
    case EBP = 'Entry-Level';
    case RM_NB = 'Best';
    case RM_SPEED = 'Good';
    case MOTOR_CORPORATE_NB_COMMERCIAL = 'Motor Corporate - NB Commercial';
    case BDM = 'BDM';
    case SBDM = 'SBDM';
    case MOTOR_COOPERATE_RENEWALS = 'Motor cooperate Renewals';
    case RM_RENEWALS = 'RM - Renewals';
    case CORPLINE_TEAM = 'Corpline - Team';
    case CORPLINE_RENEWALS = 'Corpline - Renewals';
    case HOME_RENEWALS = 'Home - Renewals';
    case PET_TEAM = 'Pet - Team';
    case PET_RENEWALS = 'Pet - Renewals';
    case YACHT_TEAM = 'Yacht - Team';
    case YACHT_RENEWALS = 'Yacht - Renewals';
    case CYCLE_RENEWALS = 'Cycle - Renewals';
    case SIC_UNASSISTED = 'SIC 2.0 Unassisted';
    case VALUE = 'Value';
    case VOLUME = 'Volume';
    case AMT = 'AMT';
    case MICRO_SME = 'Micro SME';
    case BIKE = 'Bike';
    case BIKE_TEAM = 'Bike - Team';
    case TRAVEL_RENEWALS = 'Travel - Renewals';
    case TRAVEL_TEAM = 'Travel - Team';
    case DEVICE_INSURANCE = 'Device Insurance';
    case CYBER_INSURANCE = 'Cyber Insurance';

    /**
     * Get team ID by team enum.
     *
     * @return int|null The team ID or null when unset.
     */
    public static function getTeamId(self $team): ?int
    {
        return match ($team) {
            self::CAR => 2,
            default => null,
        };
    }

    public function getQuoteTypes()
    {
        return match ($this) {
            self::CORPLINE => [
                QuoteTypes::BUSINESS,
            ],
            self::GROUP_MEDICAL => [
                QuoteTypes::BUSINESS,
            ],
            self::DEVICE_INSURANCE => [
                QuoteTypes::DEVICE,
            ],
            self::CYBER_INSURANCE => [
                QuoteTypes::CYBER,
            ],
            default => throw new \InvalidArgumentException(\sprintf('Unknown or unmapped LOB / team: %s', $this->value)),
        };
    }
}
