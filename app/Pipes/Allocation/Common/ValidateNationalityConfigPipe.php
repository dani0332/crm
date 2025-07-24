<?php

namespace App\Pipes\Allocation\Common;

use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use App\Services\NationalityAllocationService;
use Closure;

class ValidateNationalityConfigPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($this->allocationRequest->get('skipNationalityValidation', false)) {
            LoggerService::info('Skipping nationality validation due to skipNationalityValidation flag');
            $this->resolveExcludedAdvisorIds();

            return $next($request);
        }

        $config = $this->getNationalityConfig();

        if (! $config) {
            $this->resolveExcludedAdvisorIds();

            return $next($request);
        }

        $this->allocationRequest->setNationalityConfig($config);

        $advisorIds = NationalityAllocationService::getUserIDs($config);

        $this->allocationRequest->setAdvisorIDs($advisorIds);

        LoggerService::info("Nationality Config found for Nationality ID: {$this->lead->nationality_id} | Advisor IDs: ".implode(', ', $advisorIds));

        return $next($request);
    }

    private function getNationalityConfig()
    {
        return NationalityAllocationService::find($this->allocationRequest->getQuoteType(), $this->lead->nationality_id);
    }
}
