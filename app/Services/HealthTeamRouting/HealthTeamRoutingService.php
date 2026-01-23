<?php

declare(strict_types=1);

namespace App\Services\HealthTeamRouting;

use App\Models\HealthQuote;
use App\Services\Logger\LoggerService;

class HealthTeamRoutingService
{
    use HealthTeamRoutable;

    public function getTeamBasedOnHealthTeamRouting(HealthQuote $lead): ?string
    {

        LoggerService::startQuoteLogging($lead);

        $teamName = '';

        if (! $this->isHealthTeamRoutingEnabled()) {
            LoggerService::info('Health team routing is not enabled');
            return $teamName;
        }

        if ($lead->isAUHLead()) {
            $teamName = $this->getTeamBasedOnAUHLead($lead);
        } else {
            $teamName = $this->getTeamBasedOnNonAUHLead($lead);
        }

        return $teamName;
    }

    private function getTeamBasedOnAUHLead(HealthQuote $lead): ?string
    {
        return '';
    }

    private function getTeamBasedOnNonAUHLead(HealthQuote $lead): ?string
    {
        $isPECLead = $lead->hasPecTag();
        if ($isPECLead) {
            return '';
        } else {
            return '';
        }
        return '';
    }

}
