<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\TravelAllocationService;
use Illuminate\Http\Response;
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
            info(self::class." - executeSteps: Travel Allocation started for allocation id : {$this->allocationId}");
            $lead = $this->fetchLead($overrideAdvisorId);

            if (! $lead) {
                info(self::class." - executeSteps: Lead not found for : {$this->allocationId}");

                return ['advisorId' => 0, 'message' => 'Lead not found or not under fetch criteria', 'status' => Response::HTTP_NOT_FOUND]; // when lead is not on criteria or not found
            }

            $advisor = $this->fetchAvailableAdvisor();

            if (! $advisor) {
                info(self::class." - executeSteps: No advisor found against lead : {$lead->uuid}");

                return ['advisorId' => 0, 'message' => 'Advisor not found', 'status' => Response::HTTP_NOT_FOUND];
            }

            $this->assignLead($lead, $advisor); // Assign the lead to the advisor

            return ['advisorId' => $advisor->id, 'message' => 'lead allocated successfully', 'status' => Response::HTTP_OK];
        } catch (\Throwable $th) {
            $message = $th->getMessage() ?? '';
            info('exception occurred in travel lead allocation with error : '.$message);
            info('exception occurred in travel lead allocation with error stack as  : '.$th->getTraceAsString());

            return ['advisorId' => 0, 'message' => 'exception occurred in travel lead allocation with error : '.$message, 'status' => Response::HTTP_INTERNAL_SERVER_ERROR];
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
