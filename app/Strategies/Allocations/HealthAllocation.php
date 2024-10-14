<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\QuoteTypes;
use App\Services\HealthAllocationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthAllocation implements Allocation
{
    protected $healthAllocationService;
    protected $allocationId;
    private bool $overrideAdvisorId = false;

    public function __construct(HealthAllocationService $healthAllocationService, $allocationId, bool $overrideAdvisorId = false)
    {
        $this->healthAllocationService = $healthAllocationService;
        $this->allocationId = $allocationId;
        $this->overrideAdvisorId = $overrideAdvisorId;
    }

    public function executeSteps()
    {
        try {
            $lead = $this->fetchLead();

            if (! $lead) {
                info('Lead not found or not under fetch criteria for allocation id: '.$this->allocationId);

                return $this->healthAllocationService->createResponse(0, 'Lead not found or not under fetch criteria', Response::HTTP_NOT_FOUND);
            }

            $this->assignTeamBasedOnPrices($lead);

            if (! $lead->health_team_type) {
                info('No health team found against lead : '.$lead->uuid);

                return $this->healthAllocationService->createResponse(0, 'No health team found', Response::HTTP_NOT_FOUND);
            }

            $advisor = $this->fetchAvailableAdvisor($lead->health_team_type);

            if (! $advisor) {
                $this->healthAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::HEALTH);

                info('No advisors found against lead : '.$lead->uuid);

                return $this->healthAllocationService->createResponse(0, 'Advisor not found', Response::HTTP_NOT_FOUND);
            }

            $this->assignLead($lead, $advisor); // Assign the lead to the advisor

            return $this->healthAllocationService->createResponse($advisor->id, 'Advisor assigned successfully!', Response::HTTP_OK);
        } catch (\Throwable $th) {
            $this->healthAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::HEALTH);

            $message = $th->getMessage() ?? '';
            info('exception occurred in health lead allocation with error : '.$message);
            info('exception occurred in health lead allocation with error stack as  : '.$th->getTraceAsString());

            return $this->healthAllocationService->createResponse(0, 'exception occurred in health lead allocation with error : '.$message, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function fetchLead()
    {
        return $this->healthAllocationService->fetchLead($this->allocationId, $this->overrideAdvisorId);
    }

    private function assignTeamBasedOnPrices($lead)
    {
        $this->healthAllocationService->assignTeamBasedOnPrices($lead);
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
