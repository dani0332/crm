<?php

namespace App\Pipes\Allocation\Travel;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Models\TravelQuoteRequestDetail;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;

class AssignChildLeadPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        if ($this->allocationRequest->get('isMixEnquiryWithAutomation')) {
            LoggerService::info(self::class.':assignLead - Mix Enquiry with Automation so going to assign advisor to child lead');

            $this->assignAvailableAdvisorToChild(parentLead: $this->lead);
        }

        return $next($request);
    }

    private function assignAvailableAdvisorToChild(Model $parentLead)
    {
        $lead = $this->allocationRequest->model()->where('parent_id', $parentLead->id)->first();
        if ($lead) {
            try {
                LoggerService::info(self::class.":assignAvailableAdvisorToChild - Finding Advisor for Child Lead: {$lead->uuid}");
                $advisor = $this->fetchAvailableAdvisor(teamId: getTeamId(TeamNameEnum::SIC_UNASSISTED), lead: $lead);
                if (! $advisor) {
                    LoggerService::info(self::class.":assignAvailableAdvisorToChild - No Advisor found for Child Lead: {$lead->uuid}");
                    $this->leadAllocationFailed($lead->uuid, $this->allocationRequest->getQuoteType());
                } else {
                    info(self::class.":assignAvailableAdvisorToChild - Advisor found for Child Lead: {$lead->uuid}");
                    $this->assignLead($lead, $advisor, AssignmentTypeEnum::SYSTEM_ASSIGNED);
                }
            } catch (Exception $e) {
                LoggerService::error($e->getMessage(), exception: $e);
                $this->leadAllocationFailed($lead->uuid, $this->allocationRequest->getQuoteType());
            }
        }
    }

    private function fetchAvailableAdvisor($teamId, $lead)
    {
        $statusOrder = $this->getOnlineStatusesInOrder();

        foreach ($statusOrder as $status) {
            $eligibleUser = $this->findtAdvisorByStatus($status, $teamId, $lead);

            if ($eligibleUser) {
                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    private function findtAdvisorByStatus($onlineStatus, $teamId, $lead)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, $teamId, [RolesEnum::TravelAdvisor])
            ->when($lead->isSIC($this->allocationRequest->getQuoteType()), function ($q) {
                $q->where('la.is_hardstop', true); // fetch users only with hardstop as true as they are eligible for allocation
            })
            ->first();
    }

    private function assignLead($lead, $advisor, $assignmentType)
    {
        LoggerService::info(self::class.' - assignLead: Going to Assign Advisor');
        $previousAssignmentType = $lead->assignment_type;
        $previousUserId = $lead->advisor_id;
        $lead->advisor_id = $advisor->id;
        $lead->assignment_type = $assignmentType;

        $quoteBatch = $this->getQuoteBatch();
        $lead->quote_batch_id = $quoteBatch->id;
        $lead->save();

        $lead->endAllocation();

        LoggerService::info(self::class." - Assigned to advisor : {$advisor->name} Quote Batch with ID: {$quoteBatch->id} and Name: {$quoteBatch->name}");

        $previousAdvisorAssignedDate = $this->updateTravelQuoteDetail($lead->id);

        if ($lead->source != LeadSourceEnum::REFERRAL) {
            LoggerService::info(self::class.' - lead source is not referral so about to update allocation record');

            $quoteTypeId = $this->allocationRequest->getQuoteType()->id();

            if ($assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED) {
                $this->addAllocationCounts($advisor->id, $quoteTypeId);
            } else {
                $this->adjustAllocationCounts($advisor->id, $lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, $quoteTypeId);
            }
        }
    }

    protected function updateTravelQuoteDetail($leadId)
    {
        $quoteDetail = TravelQuoteRequestDetail::where('travel_quote_request_id', $leadId)->first();
        $oldAdvisorAssignedDate = $quoteDetail->advisor_assigned_date ?? '';
        $this->upsertQuoteDetail($leadId, TravelQuoteRequestDetail::class, 'travel_quote_request_id');

        return $oldAdvisorAssignedDate;
    }
}
