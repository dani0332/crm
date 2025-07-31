<?php

namespace App\Pipes\Allocation\Travel;

use App\Models\TravelQuoteRequestDetail;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class AssignLeadPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $passable
     * @return mixed
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $advisor = $this->allocationRequest->getAdvisor();

        if (! $advisor) {
            LoggerService::info('No advisor available in AssignLeadPipe - cannot proceed with assignment');
            $this->allocationRequest->markAsFailed();
            $this->throw('Advisor not found', self::OK);
        }

        $this->assign();

        return $next($request);
    }

    protected function updateQuoteDetail()
    {
        info(self::class." - about to update travel quote detail record for : {$this->lead->id}");

        $quoteDetail = TravelQuoteRequestDetail::where('travel_quote_request_id', $this->lead->id)->first();
        $oldAdvisorAssignedDate = $quoteDetail->advisor_assigned_date ?? '';
        $this->upsertQuoteDetail($this->lead->id, TravelQuoteRequestDetail::class, 'travel_quote_request_id');

        return $oldAdvisorAssignedDate;
    }
}
