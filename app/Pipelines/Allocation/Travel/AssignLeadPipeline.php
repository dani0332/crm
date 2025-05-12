<?php

namespace App\Pipelines\Allocation\Travel;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\ProcessTracker\StepsEnums\ProcessTrackerAllocationEnum;
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
            $this->assignLead();

            $this->lead->endAllocation();

            $this->allocationRequest->set('isSuccess', true);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            LoggerService::error($e->getMessage(), exception: $e);

            $this->allocationRequest->getTracker()->saveResult(ProcessTrackerAllocationEnum::EXCEPTION_RAISED, summary: "Lead allocation failed with error : {$e->getMessage()}");

            $this->throw('Lead allocation failed', self::SERVER_ERROR);
        }

        return $next($request);
    }

    private function assignLead()
    {
        $advisor = $this->allocationRequest->get('advisor');
        $assignmentType = $this->allocationRequest->getAssignmentType();
        $tracker = $this->allocationRequest->getTracker();

        LoggerService::info(self::class.' - assignLead: Going to Assign Advisor');
        $previousAssignmentType = $this->lead->assignment_type;
        $previousUserId = $this->lead->advisor_id;
        $this->lead->advisor_id = $advisor->id;
        $this->lead->assignment_type = $assignmentType;

        $quoteBatch = $this->getQuoteBatch();
        $this->lead->quote_batch_id = $quoteBatch->id;
        $this->lead->save();

        $this->lead->endAllocation();

        LoggerService::info(self::class." - Assigned to advisor : {$advisor->name} Quote Batch with ID: {$quoteBatch->id} and Name: {$quoteBatch->name}");

        $previousAdvisorAssignedDate = $this->updateQuoteDetail($this->lead->id);

        if ($tracker) {
            $tracker->saveResult(
                ProcessTrackerAllocationEnum::LEAD_ASSIGNED,
                [
                    'leadId' => $this->lead->id,
                    'leadUuid' => $this->lead->uuid,
                    'advisorId' => $advisor->id,
                    'advisorName' => $advisor->name,
                    'advisorEmail' => $advisor->email,
                    'quoteBatchId' => $quoteBatch->id,
                    'quoteBatchName' => $quoteBatch->name,
                    'previousAssignmentType' => $previousAssignmentType,
                    'previousUserId' => $previousUserId,
                    'previousAdvisorAssignedDate' => $previousAdvisorAssignedDate,
                ],
                "Lead assigned to advisor: {$advisor->name} with Quote Batch ID: {$quoteBatch->id} and Batch Name: {$quoteBatch->name}",
                isSuccess: true
            );
        }

        if ($this->lead->source != LeadSourceEnum::REFERRAL) {
            LoggerService::info(self::class.' - lead source is not referral so about to update allocation record');
            $assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED ? $this->addAllocationCounts($advisor->id, $this->allocationRequest->getQuoteType()->id()) : $this->adjustAllocationCounts($advisor->id, $this->lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, $this->allocationRequest->getQuoteType()->id());
        }
    }

    private function updateQuoteDetail($leadId)
    {
        info(self::class." - about to update travel quote detail record for : {$leadId}");

        $quoteDetail = TravelQuoteRequestDetail::where('travel_quote_request_id', $leadId)->first();
        $oldAdvisorAssignedDate = $quoteDetail->advisor_assigned_date ?? '';
        $this->upsertQuoteDetail($leadId, TravelQuoteRequestDetail::class, 'travel_quote_request_id');

        return $oldAdvisorAssignedDate;
    }
}
