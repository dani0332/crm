<?php

namespace App\Pipes\Allocation\Health;

use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;
use Illuminate\Support\Facades\DB;

class AssignLeadPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $advisor = $this->allocationRequest->getAdvisor();

        if (!$advisor) {
            $this->throw('No advisor found to assign', self::NOT_FOUND);
        }

        $this->assignLead($advisor);
        $this->lead->endAllocation();

        $this->allocationRequest->markAsAllocated();

        return $next($request);
    }

    protected function assignLead($advisor)
    {
        LoggerService::info("Assigning health lead {$this->lead->uuid} to advisor {$advisor->id}");

        DB::beginTransaction();
        try {
            // Update the lead with the new advisor
            $this->lead->advisor_id = $advisor->id;
            $this->lead->assignment_type = $this->allocationRequest->getAssignmentType();
            $this->lead->save();

            // Update the advisor's allocation count
            $leadAllocation = $advisor->leadAllocation()
                ->where('quote_type_id', $this->allocationRequest->getQuoteType()->id())
                ->first();

            if ($leadAllocation) {
                $leadAllocation->allocation_count = $leadAllocation->allocation_count + 1;
                $leadAllocation->last_allocated = now();
                $leadAllocation->save();
            }

            // If this is a buy lead, update the buy lead allocation count
            if ($this->allocationRequest->get('buyLeadRequest')) {
                $leadAllocation->buy_lead_allocation_count = $leadAllocation->buy_lead_allocation_count + 1;
                $leadAllocation->buy_lead_last_allocated = now();
                $leadAllocation->save();

                $this->allocationRequest->get('buyLeadRequest')->endProcessing();
            }

            DB::commit();

            LoggerService::info("Successfully assigned lead {$this->lead->uuid} to advisor {$advisor->id}");
        } catch (\Exception $e) {
            DB::rollback();
            LoggerService::error("Failed to assign lead: {$e->getMessage()}");
            $this->throw("Failed to assign lead: {$e->getMessage()}", self::SERVER_ERROR);
        }
    }
}
