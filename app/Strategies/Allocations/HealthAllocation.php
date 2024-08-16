<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Services\HealthAllocationService;
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

                return ['advisorId' => 0, 'message' => 'Lead not found or not under fetch criteria', 'status' => Response::HTTP_NOT_FOUND]; // when lead is not on criteria or not found
            }

            $this->assignTeamBasedOnPrice($lead);

            if (! $lead->health_team_type) {
                info('No health team found against lead : '.$lead->uuid);

                return ['advisorId' => 0, 'message' => 'No health team found', 'status' => Response::HTTP_NOT_FOUND]; // when system is not able to identify sub team based on price
            }

            $advisor = $this->fetchAvailableAdvisor($lead->health_team_type);

            if (! $advisor) {
                info('No advisors found against lead : '.$lead->uuid);

                return ['advisorId' => 0, 'message' => 'Advisor not found', 'status' => Response::HTTP_NOT_FOUND]; // when no advisor is found
            }

            $this->assignLead($lead, $advisor); // Assign the lead to the advisor

            return ['advisorId' => $advisor->id, 'message' => 'Advisor assigned successfully!', 'status' => Response::HTTP_OK];
        } catch (\Throwable $th) {
            $message = $th->getMessage() ?? '';
            info('exception occurred in health lead allocation with error : '.$message);
            info('exception occurred in health lead allocation with error stack as  : '.$th->getTraceAsString());

            return ['advisorId' => 0, 'message' => 'exception occurred in health lead allocation with error : '.$message, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
        }
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
