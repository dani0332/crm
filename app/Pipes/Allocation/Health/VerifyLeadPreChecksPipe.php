<?php

namespace App\Pipes\Allocation\Health;

use App\Enums\LeadSourceEnum;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use Closure;

class VerifyLeadPreChecksPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $lead = $this->findLead();

        if (! $lead) {
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        }

        $this->allocationRequest->setLead($lead);

        return $next($request);
    }

    private function findLead()
    {
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
