<?php

namespace App\Pipes\Allocation\Cyber;

use App\Enums\TeamNameEnum;
use App\Models\PersonalQuote;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class EvaluateTeamPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - Starting team evaluation for Cyber lead');
        
        $this->setRequest($request);

        $lead = $this->allocationRequest->getLead();

        $teamId = $this->evaluateTeamId($lead);

        $this->allocationRequest->setTeamId($teamId);

        LoggerService::info(self::class.' - Team evaluation completed for Cyber lead', extra: [
            'teamId' => $teamId,
            'willUseTeam' => $teamId ? true : false,
        ]);

        return $next($request);
    }

    private function evaluateTeamId(PersonalQuote $lead)
    {
        $defaultTeamId = false;
        
        $isPaymentAuthorizedOrDeclined = $lead->isPaymentAuthorizedOrDeclined();
        
        $cyberQuoteRequest = $lead->cyberQuoteRequest;
        $sicAdvisorRequested = false;
        
        if ($cyberQuoteRequest && isset($cyberQuoteRequest->sic_advisor_requested)) {
            $sicAdvisorRequested = (bool) $cyberQuoteRequest->sic_advisor_requested;
        }

        LoggerService::info(self::class.' - Cyber lead conditions evaluation', extra: [
            'isPaymentAuthorizedOrDeclined' => $isPaymentAuthorizedOrDeclined,
            'sicAdvisorRequested' => $sicAdvisorRequested,
            'cyberQuoteRequestExists' => $cyberQuoteRequest ? true : false,
        ]);

        if ($isPaymentAuthorizedOrDeclined) {
            $teamId = getTeamId(TeamNameEnum::TRAVEL_TEAM);
            
            LoggerService::info(self::class.' - Cyber lead is paid - Assigning to HAPEX team', extra: [
                'teamId' => $teamId,
                'teamName' => TeamNameEnum::TRAVEL_TEAM,
                'reason' => 'Payment authorized or declined',
            ]);

            return $teamId;
        }

        if (! $sicAdvisorRequested) {
            LoggerService::info(self::class.' - sic advisor requested is false for cyber lead - Stopping allocation', extra: [
                'reason' => 'Unpaid lead without SIC advisor request',
            ]);

            $this->stop('sic advisor requested is false for cyber lead', self::OK);
        }

        LoggerService::info(self::class.' - sic advisor requested is true for cyber lead - Will be assigned using hardcoded emails', extra: [
            'teamId' => $defaultTeamId,
        ]);

        return $defaultTeamId;
    }
}
