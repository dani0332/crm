<?php

namespace App\Pipes\Allocation\Cyber;

use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class VerifyLeadPreChecksPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - Starting pre-checks for Cyber lead');

        $this->setRequest($request);

        $lead = $this->findLead();

        if (! $lead) {
            LoggerService::info(self::class.' - Cyber lead does not meet pre-check criteria or not found');
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        }

        LoggerService::info(self::class.' - Cyber lead pre-checks passed', extra: [
            'leadUuid' => $lead->uuid,
            'leadId' => $lead->id,
        ]);

        $this->allocationRequest->setLead($lead);

        return $next($request);
    }

    private function findLead()
    {
        LoggerService::info(self::class.' - Fetching Cyber lead from database');

        $lead = $this->getLeadBaseQuery()->first();

        if (! $lead) {
            LoggerService::info(self::class.' - Cyber lead not found in database');

            return null;
        }

        LoggerService::info(self::class.' - Cyber lead found successfully', extra: [
            'leadUuid' => $lead->uuid,
            'leadId' => $lead->id,
            'hasAdvisor' => $lead->advisor_id ? true : false,
        ]);

        return $lead;
    }
}

