<?php

namespace App\Strategies;

use App\Enums\AssignmentTypeEnum;
use App\Services\HealthAllocationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthAllocation implements Allocation
{
    protected $healthAllocationService;
    protected $allocationId;

    public function __construct(HealthAllocationService $healthAllocationService, $allocationId)
    {
        $this->healthAllocationService = $healthAllocationService;
        $this->allocationId = $allocationId;
    }

    public function executeSteps($overrideAdvisorId = false)
    {
        $lead = $this->fetchLead($overrideAdvisorId);

        if (! $lead) {

            return false; // when lead is not on criteria or not found
        }

        $this->assignTeamBasedOnPrice($lead);

        if (! $lead->health_team_type) {
            info('No health team found against lead : '.$lead->uuid);

            return 0; // when system is not able to identify sub team based on price
        }

        $advisor = $this->fetchAvailableAdvisor($lead->health_team_type);

        if (! $advisor) {
            info('No advisors found against lead : '.$lead->uuid);

            return 0; // when no advisor is found
        }

        $this->assignLead($lead, $advisor); // Assign the lead to the advisor

        return $advisor->id;
    }

    private function fetchLead($overrideAdvisorId)
    {
        return $this->healthAllocationService->fetchLead($this->allocationId, $overrideAdvisorId);
    }

    private function assignTeamBasedOnPrice($lead)
    {
        $this->healthAllocationService->assignTeamBasedOnPrice($lead);
    }

    private function fetchAvailableAdvisor($leadTeam)
    {
        return $this->healthAllocationService->fetchAvailableAdvisor($leadTeam, false);
    }

    private function assignLead($lead, $advisor)
    {
        DB::beginTransaction();
        try {
            $this->healthAllocationService->assignLead($lead, $advisor, AssignmentTypeEnum::SYSTEM_ASSIGNED);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
        }
    }
}
