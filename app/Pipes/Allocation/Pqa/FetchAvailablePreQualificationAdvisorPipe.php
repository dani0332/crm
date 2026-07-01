<?php

declare(strict_types=1);

namespace App\Pipes\Allocation\Pqa;

use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchAvailablePreQualificationAdvisorPipe extends BasePqaAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - fetching available Pre Qualification Advisor');

        $this->setRequest($request);

        $advisor = $this->findAvailablePreQualificationAdvisor();

        if (! $advisor) {
            LoggerService::info(self::class.' - no Pre Qualification Advisor available');
            $this->allocationRequest->markAsFailed();
            $this->throw('Pre Qualification Advisor not found', self::NOT_FOUND);
        }

        LoggerService::info(self::class.' - Pre Qualification Advisor found', extra: [
            'advisorId' => $advisor->id,
            'advisorName' => $advisor->name,
            'advisorEmail' => $advisor->email,
        ]);

        $this->allocationRequest->setAdvisor($advisor);

        return $next($request);
    }
}
