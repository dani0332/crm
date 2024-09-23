<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Factories\AllocationFactory;
use App\Services\HealthAllocationService;
use App\Services\HealthEmailService;
use Carbon\Carbon;
use Illuminate\Http\Response;
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
        try {
            $lead = $this->fetchLead($overrideAdvisorId);

            if (! $lead) {
                info('Lead not found or not under fetch criteria for allocation id: '.$this->allocationId);

                return AllocationFactory::createResponse(0, 'Lead not found or not under fetch criteria', Response::HTTP_NOT_FOUND);
            }

            $this->assignTeamBasedOnPrices($lead);

            if (! $lead->health_team_type) {
                info('No health team found against lead : '.$lead->uuid);

                return AllocationFactory::createResponse(0, 'No health team found', Response::HTTP_NOT_FOUND);
            }

            $advisor = $this->fetchAvailableAdvisor($lead->health_team_type);

            if (! $advisor) {
                info('No advisors found against lead : '.$lead->uuid);

                if ($lead->isApplicationPending() && ! $lead->isApplyNowEmailSent() && Carbon::parse($lead->quote_status_date)->lessThanOrEqualTo(now()->subMinutes(10))) {
                    info("Sending Apply Now Email for uuid {$lead->uuid} as it's been 10 minutes since quote status was marked as applicatio pending");
                    app(HealthEmailService::class)->initiateApplyNowEmail($lead);
                }

                return AllocationFactory::createResponse(0, 'Advisor not found', Response::HTTP_NOT_FOUND);
            }

            $this->assignLead($lead, $advisor); // Assign the lead to the advisor

            return AllocationFactory::createResponse($advisor->id, 'Advisor assigned successfully!', Response::HTTP_OK);
        } catch (\Throwable $th) {
            $message = $th->getMessage() ?? '';
            info('exception occurred in health lead allocation with error : '.$message);
            info('exception occurred in health lead allocation with error stack as  : '.$th->getTraceAsString());

            return AllocationFactory::createResponse(0, 'exception occurred in health lead allocation with error : '.$message, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function fetchLead($overrideAdvisorId)
    {
        return $this->healthAllocationService->fetchLead($this->allocationId, $overrideAdvisorId);
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
