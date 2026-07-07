<?php

namespace App\Pipes\Allocation\Health;

use App\Enums\LeadSourceEnum;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class VerifyLeadPreChecksPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($this->lead->has_pec_tag && ! empty($this->lead->price_starting_from) && ($this->allocationRequest->isOverrideAdvisorRequest() || empty($this->lead->advisor_id))) {
            LoggerService::info('Lead has PEC tag and price starting from, Continuing allocation');

            return $next($request);
        }

        if ($this->lead->hasAdnicPlan()) {
            LoggerService::info('Lead has ADNIC plan, Continuing allocation');

            return $next($request);
        }

        $lead = $this->findLead();

        if (! $lead) {
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        }

        $this->allocationRequest->setLead($lead);

        return $next($request);
    }

    private function findLead()
    {
        if ($this->lead?->source === LeadSourceEnum::EA_IMCRM) {
            LoggerService::info(self::class.' - EA_IMCRM lead detected, bypassing pre-checks and proceeding with ILA allocation');

            return $this->getLeadBaseQuery()->first();
        }

        return $this->getLeadBaseQuery()
            ->where(function ($query) {
                // First condition: either `sic_advisor_requested` is 1 or `source` is not `REVIVAL`
                $query->where('sic_advisor_requested', 1)
                    ->orWhereNotIn('source', [LeadSourceEnum::REVIVAL]);
            })
            ->where(function ($query) {
                // Second condition: applies if the first condition is false
                $query->whereIn('source', [LeadSourceEnum::REVIVAL, LeadSourceEnum::REVIVAL_REPLIED])
                    ->orWhereNotNull('health_quote_request.price_starting_from');
            })
            ->first();
    }
}
