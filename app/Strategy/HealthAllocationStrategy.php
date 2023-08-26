<?php

namespace App\Strategy;

use App\Enums\AssignmentTypeEnum;
use App\Services\HealthAllocationService;

class HealthAllocationStrategy implements AllocationStrategy
{
    protected $healthAllocationService;
    protected $allocationId;

    public function __construct(HealthAllocationService $healthAllocationService, $allocationId)
    {
        $this->healthAllocationService = $healthAllocationService;
        $this->allocationId = $allocationId;
    }

    public function executeSteps()
    {

        $lead = $this->fetchLead();

        if (! $lead) {
            return false;
        } // when lead is not on criteria or not found

        $this->assignTeamBasedOnPrice($lead);

        if (! $lead->health_team_type) {
            return false;
        } // when system is not able to identify sub team based on price

        $advisor = $this->fetchAvailableAdvisor($lead->health_team_type);

        if (! $advisor) {
            return false;
        } // when no advisor is found

        $this->assignLead($lead, $advisor);
    }

    private function fetchLead()
    {
        return $this->healthAllocationService->fetchLead($this->allocationId);
    }

    private function assignTeamBasedOnPrice($lead)
    {
        $this->healthAllocationService->assignTeamBasedOnPrice($lead);
    }

    private function fetchAvailableAdvisor($leadTeam)
    {
        return $this->healthAllocationService->fetchAvailableAdvisor($leadTeam);
    }

    private function assignLead($lead, $advisor)
    {
        $this->healthAllocationService->assignLead($lead, $advisor, AssignmentTypeEnum::SYSTEM_ASSIGNED);
    }
}
