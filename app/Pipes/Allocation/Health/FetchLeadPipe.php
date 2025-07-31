<?php

namespace App\Pipes\Allocation\Health;

use App\Models\HealthQuote;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchLeadPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request, true);

        $lead = $this->fetchLead();

        if (! $lead) {
            LoggerService::info('Lead not found or not under fetch criteria');
            $this->throw('Lead not found or not under fetch criteria', self::NOT_FOUND);
        }

        $this->allocationRequest->setLead($lead);

        return $next($request);
    }

    protected function fetchLead()
    {
        return HealthQuote::where('uuid', $this->allocationRequest->getQuoteUUID())
            ->when($this->allocationRequest->isOverrideAdvisorRequest(), function ($query) {
                return $query;
            }, function ($query) {
                return $query->where(function ($query) {
                    $query->whereNull('advisor_id')
                        ->orWhere('advisor_id', 0);
                });
            })
            ->first();
    }
}
