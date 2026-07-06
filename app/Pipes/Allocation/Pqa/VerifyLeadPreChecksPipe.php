<?php

declare(strict_types=1);

namespace App\Pipes\Allocation\Pqa;

use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class VerifyLeadPreChecksPipe extends BasePqaAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if (! $this->lead) {
            $this->throw('Lead not found', self::NOT_FOUND);
        }

        if (! $this->allocationRequest->isOverrideAdvisorRequest() && ! empty($this->lead->pq_advisor_id)) {
            LoggerService::info(self::class.' - PQA already assigned', extra: [
                'pq_advisor_id' => $this->lead->pq_advisor_id,
            ]);
            $this->throw('Pre Qualification Advisor already assigned', self::OK);
        }

        if ($this->lead->isFakeOrDuplicate()) {
            LoggerService::info(self::class.' - Lead is fake or duplicate, skipping PQA allocation');
            $this->throw('Lead does not meet pre-check criteria', self::NOT_FOUND);
        }

        LoggerService::info(self::class.' - PQA pre-checks passed', extra: [
            'leadUuid' => $this->lead->uuid,
        ]);

        return $next($request);
    }
}
