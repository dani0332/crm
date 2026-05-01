<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\LeadSourceEnum;
use App\Enums\TeamNameEnum;
use App\Models\CarQuote;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class EvaluateTeamPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $lead = $this->allocationRequest->getLead();

        $teamId = $this->evaluateTeamId($lead);

        $this->allocationRequest->setTeamId($teamId);

        return $next($request);
    }

    private function evaluateTeamId(CarQuote $lead)
    {
        $teamName = null;

        $isSIC = $this->allocationRequest->isSIC();
        $isAIG = $this->allocationRequest->isAIG();

        if ($lead->isPUA() || ($isAIG && $lead->sic_advisor_requested)) {
            $teamName = TeamNameEnum::ORGANIC;

            LoggerService::info('Lead is PUA or AIG with SIC advisor requested. Assigning to Organic team.', [
                'isSIC' => $isSIC,
                'isAIG' => $isAIG,
                'isPUA' => $lead->isPUA(),
                'sicAdvisorRequested' => $lead->sic_advisor_requested,
            ]);
        } elseif ($lead->isRevivalCommsIntentHighOrMedium()) {
            $teamName = TeamNameEnum::ORGANIC;
            LoggerService::info('Lead is a Revival lead and has intent high or medium. Assigning to Organic team.', [
                'engagement_level' => $lead->carQuoteRequestDetail?->engagement_level,
            ]);
        } elseif ($isSIC && $lead->isPaymentAuthorizedOrDeclined() && ! $lead->isPaymentLinkRequested() && ! in_array($lead->source, [LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID])) {
            $teamName = TeamNameEnum::SIC_UNASSISTED;
            LoggerService::info('SIC lead detected with payment authorized only. Assigning to SIC Unassisted team.');
        } elseif ($isSIC && $lead->isPaymentLinkRequested()) {
            LoggerService::info('SIC lead detected with payment link requested. Assigning to mapped nationality users.');
        }

        if (empty($teamName)) {
            return $this->allocationRequest->getTeamId();
        }

        return getTeamId($teamName);
    }
}
