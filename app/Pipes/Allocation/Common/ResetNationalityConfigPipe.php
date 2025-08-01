<?php

namespace App\Pipes\Allocation\Common;

use App\Enums\QuoteTypes;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class ResetNationalityConfigPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);
        LoggerService::info('ResetNationalityConfigPipe::handle - Resetting nationality config');

        if ($this->allocationRequest->hasNationalityConfig() && $this->isAdvisorNotFound()) {
            $this->allocationRequest->resetNationalityConfig();
            $this->resolveExcludedAdvisorIds();
            LoggerService::info('ResetNationalityConfigPipe::handle - Going to retry advisor allocation');
        } else {
            $this->allocationRequest->set('skipAdvisorEligibilityFetch', true);
            $this->allocationRequest->set('skipRuleExclusion', true);
        }

        return $next($request);
    }

    private function isAdvisorNotFound()
    {
        LoggerService::info(self::class.' - isAdvisorNotFound', extra: [
            'quoteType' => $this->allocationRequest->getQuoteType(),
        ]);
        if ($this->allocationRequest->getQuoteType() == QuoteTypes::CAR) {
            return empty($this->allocationRequest->get('eligibleAdvisors'));
        }

        return empty($this->allocationRequest->getAdvisor());
    }
}
