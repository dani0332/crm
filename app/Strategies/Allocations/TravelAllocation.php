<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\TravelAllocationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TravelAllocation implements Allocation
{
    public $travelAllocationService;
    public $allocationId;
    public $teamId;

    public function __construct(TravelAllocationService $travelAllocationService, $allocationId, $teamId = false)
    {
        $this->travelAllocationService = $travelAllocationService;
        $this->allocationId = $allocationId;
        $this->teamId = $teamId;
    }

    public function executeSteps($overrideAdvisorId = false)
    {
        try {
            info(self::class . " - executeSteps: Travel Allocation started for allocation id : {$this->allocationId}");
            $lead = $this->fetchLead($overrideAdvisorId);

            if (! $lead) {
                info(self::class . " - executeSteps: Lead not found for : {$this->allocationId}");

                return 'Lead not found or not under fetch criteria'; // when lead is not on criteria or not found
            }

            $advisor = $this->fetchAvailableAdvisor();

            if (! $advisor) {
                info(self::class . " - executeSteps: No advisor found against lead : {$lead->uuid}");

                return 0; // when no advisor is found
                return 'Advisor not found';
            }

            $this->assignLead($lead, $advisor); // Assign the lead to the advisor

            return $advisor->id;
        } catch (\Throwable $th) {
            info('exception occurred in car lead allocation with error : ' . $th->getMessage());
            info('exception occurred in car lead allocation with error stack as  : ' . $th->getTraceAsString());

            return 'exception occurred in bike lead allocation with error : ' . $th->getMessage();
        }
    }

    private function fetchLead($overrideAdvisorId)
    {
        return $this->travelAllocationService->fetchLead($this->allocationId, $overrideAdvisorId);
    }

    private function fetchAvailableAdvisor()
    {
        return $this->travelAllocationService->fetchAvailableAdvisor(teamId: $this->teamId, quoteUUID: $this->allocationId);
    }

    private function assignLead(TravelQuote $lead, User $advisor)
    {
        DB::beginTransaction();
        try {
            $this->travelAllocationService->assignLead($lead, $advisor, AssignmentTypeEnum::SYSTEM_ASSIGNED);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
        }
    }
}
