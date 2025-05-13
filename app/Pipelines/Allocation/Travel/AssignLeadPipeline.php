<?php

namespace App\Pipelines\Allocation\Travel;

use App\Models\TravelQuoteRequestDetail;
use App\Pipelines\Allocation\Common\BaseAllocationPipeline;
use App\Services\Logger\LoggerService;
use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;
use Closure;
use Illuminate\Support\Facades\DB;

class AssignLeadPipeline extends BaseAllocationPipeline
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

        DB::beginTransaction();

        try {
            $this->assign();

            $this->allocationRequest->set('isSuccess', true);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            LoggerService::error($e->getMessage(), exception: $e);

            $this->throw('Lead allocation failed', self::SERVER_ERROR);
        }

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
