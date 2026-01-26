<?php

declare(strict_types=1);

namespace App\Services\HealthTeamRouting;

use App\Enums\TeamCategoryEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\HealthQuote;
use App\Models\Team;
use App\Services\Logger\LoggerService;

class HealthTeamRoutingService
{
    use HealthTeamRoutable;

    public function getTeamBasedOnHealthTeamRouting(HealthQuote $lead): ?string
    {

        LoggerService::startQuoteLogging($lead);

        $teamName = '';

        if (! $this->isHealthTeamRoutingEnabled()) {
            // check if the team routing is enabled
            LoggerService::info('Health team routing is not enabled');
            return $teamName;
        }

        // check if the lead is AUH
        if ($lead->isAUHLead() || $lead->isAUHLead(false)) {
            // get team name based on AUH lead
            $teamName = $this->getTeamBasedOnAUHLead($lead);
        } else {
            // get team name based on non AUH lead
            $teamName = $this->getTeamBasedOnNonAUHLead($lead);
        }

        return $teamName;
    }

    private function getTeamBasedOnAUHLead(HealthQuote $lead): ?string
    {
        return $this->fetchTeamNameBasedOnPrice($lead, TeamCategoryEnum::AUH);
    }

    private function getTeamBasedOnNonAUHLead(HealthQuote $lead): ?string
    {
        $isPECLead = $lead->hasPecTag();
        $nonAUHTeamCategory = TeamCategoryEnum::NON_AUH;
        if ($isPECLead) {
            return $this->fetchPecTeamName();
        } else {
            return $this->fetchTeamNameBasedOnPrice($lead, $nonAUHTeamCategory);
        }
    }

    private function fetchTeamNameBasedOnPrice(HealthQuote $lead, TeamCategoryEnum $category): ?string
    {
        // fetch team name based on price and category
        return Team::where('allocation_threshold_enabled', true)
            ->where('min_price', '<=', $lead->price_starting_from)
            ->where('max_price', '>=', $lead->price_starting_from)
            ->where('category', $category->value)
            ->first()?->name;
    }

    private function fetchPecTeamName(): ?string
    {
        return Team::where('allocation_threshold_enabled', true)
            ->where('category', TeamCategoryEnum::NON_AUH->value)
            ->where('type', TeamTypeEnum::TEAM)
            ->where('name', TeamNameEnum::NON_AUH_PEC)
            ->first()?->name;
    }

}
