<?php

namespace App\Pipes\Allocation\Cyber;

use App\Models\PersonalQuote;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\CyberAllocation;
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
        $hasRetryFlag = $lead->isAllocationFailed();
        
        $cyberQuoteRequest = $lead->cyberQuoteRequest;
        $sicAdvisorRequested = false;
        
        if ($cyberQuoteRequest && isset($cyberQuoteRequest->sic_advisor_requested)) {
            $sicAdvisorRequested = (bool) $cyberQuoteRequest->sic_advisor_requested;
        }

        LoggerService::info(self::class.' - Cyber lead conditions evaluation', extra: [
            'isPaymentAuthorizedOrDeclined' => $isPaymentAuthorizedOrDeclined,
            'sicAdvisorRequested' => $sicAdvisorRequested,
            'cyberQuoteRequestExists' => $cyberQuoteRequest ? true : false,
            'hasRetryFlag' => $hasRetryFlag,
        ]);

        // If cyberr lead is paid, assign to Happiness Support User
        if ($isPaymentAuthorizedOrDeclined) {
            $this->allocationRequest->setAssignToHappinessUser(true);
            
            LoggerService::info(self::class.' - Cyber lead is paid - Will assign to Happiness Support User', extra: [
                'targetUserEmail' => CyberAllocation::HAPPINESS_SUPPORT_USER_EMAIL,
                'reason' => 'Payment authorized or declined',
            ]);

            return false;
        }

        // SIC advisor requested or has retry flag, assign to hardcoded advisors
        if ($sicAdvisorRequested || $hasRetryFlag) {
            $reason = $sicAdvisorRequested 
                ? 'SIC advisor explicitly requested' 
                : 'Lead has retry flag (lead_allocation_failed_at)';
            
            LoggerService::info(self::class.' - Cyber lead will be assigned to hardcoded advisors', extra: [
                'teamId' => $defaultTeamId,
                'reason' => $reason,
                'sicAdvisorRequested' => $sicAdvisorRequested,
                'hasRetryFlag' => $hasRetryFlag,
            ]);

            return $defaultTeamId;
        }

        // Lead doesn't meet allocation criteria - stop allocation
        LoggerService::info(self::class.' - sic advisor requested is false and no retry flag - Stopping allocation', extra: [
            'reason' => 'Unpaid lead without SIC advisor request or retry flag',
        ]);

        $this->stop('sic advisor requested is false for cyber lead', self::OK);
    }
}
