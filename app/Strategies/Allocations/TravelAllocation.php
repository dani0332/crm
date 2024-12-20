<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\QuoteTypes;
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
    private bool $overrideAdvisorId = false;

    public function __construct(TravelAllocationService $travelAllocationService, $allocationId, $teamId = false, bool $overrideAdvisorId = false)
    {
        $this->travelAllocationService = $travelAllocationService;
        $this->allocationId = $allocationId;
        $this->teamId = $teamId;
        $this->overrideAdvisorId = $overrideAdvisorId;
    }

    public function executeSteps()
    {
        $this->travelAllocationService->resetProps();

        $response = [
            'advisorId' => 0,
            'message' => '',
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];

        try {
            info(self::class." - executeSteps: Travel Allocation started for allocation id : {$this->allocationId}");
            $lead = $this->fetchLead();

            if (! $lead) {
                info(self::class." - executeSteps: Lead not found for : {$this->allocationId}");
                $response = $this->travelAllocationService->createResponse(0, 'Lead not found or not under fetch criteria', Response::HTTP_NOT_FOUND);
            } else {
                $advisor = $this->fetchAvailableAdvisor($lead);

                if (! $advisor) {
                    $this->travelAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::TRAVEL);

                    info(self::class." - executeSteps: No advisor found against lead : {$lead->uuid}");

                    $response = $this->travelAllocationService->createResponse(0, 'Advisor not found', Response::HTTP_NOT_FOUND);
                } else {
                    $this->assignLead($lead, $advisor); // Assign the lead to the advisor
                    $response = $this->travelAllocationService->createResponse($advisor->id, 'Advisor assigned successfully!', Response::HTTP_OK);
                }
            }
        } catch (\Throwable $th) {
            $this->travelAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::TRAVEL);

            $message = $th->getMessage() ?? '';
            info('exception occurred in travel lead allocation with error : '.$message);
            info('exception occurred in travel lead allocation with error stack as  : '.$th->getTraceAsString());
            $response = $this->travelAllocationService->createResponse(0, 'exception occurred in travel lead allocation with error : '.$message, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->travelAllocationService->resetProps();

        return $response;
    }

    private function fetchLead()
    {
        return $this->travelAllocationService->fetchLead($this->allocationId, $this->overrideAdvisorId);
    }

    private function fetchAvailableAdvisor(TravelQuote $lead)
    {
        return $this->travelAllocationService->fetchAvailableAdvisor(teamId: $this->teamId, quoteUUID: $this->allocationId, lead: $lead);
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
