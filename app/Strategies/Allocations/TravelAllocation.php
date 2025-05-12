<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\ProcessTracker\StepsEnums\ProcessTrackerAllocationEnum;
use App\Enums\QuoteTypes;
use App\Exceptions\Allocation\AllocationException;
use App\Models\TravelQuote;
use App\Models\User;
use App\Pipelines\Allocation\Common\VerifyAlreadyInProgressAllocationPipeline;
use App\Pipelines\Allocation\Travel\FetchAvailableAdvisorPipeline;
use App\Pipelines\Allocation\Travel\FetchLeadPipeline;
use App\Services\Logger\LoggerService;
use App\Services\ProcessTracker\ProcessTrackerService;
use App\Services\TravelAllocationService;
use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;
use Throwable;

class TravelAllocation implements Allocation
{
    public $travelAllocationService;
    public $allocationId;
    public $teamId;
    public $tracker;
    private bool $overrideAdvisorId = false;

    public function __construct(TravelAllocationService $travelAllocationService, ProcessTrackerService $tracker, $allocationId, $teamId = false, bool $overrideAdvisorId = false)
    {
        $this->travelAllocationService = $travelAllocationService;
        $this->allocationId = $allocationId;
        $this->teamId = $teamId;
        $this->tracker = $tracker;
        $this->overrideAdvisorId = $overrideAdvisorId;
    }

    public function executeSteps()
    {
        $this->travelAllocationService->resetProps();

        $alloctionRequest = new AllocationRequest(
            quoteType: QuoteTypes::TRAVEL,
            quoteUUID: $this->allocationId,
            teamId: $this->teamId,
            overrideAdvisorId: $this->overrideAdvisorId,
            tracker: $this->tracker
        );

        try {
            return Pipeline::send($alloctionRequest)->through([
                FetchLeadPipeline::class,
                VerifyAlreadyInProgressAllocationPipeline::class,
                FetchAvailableAdvisorPipeline::class,
            ])->then(function ($result) {
                return $result;
            });
        } catch (AllocationException|Throwable $e) {
            $this->travelAllocationService->leadAllocationFailed($this->allocationId, QuoteTypes::TRAVEL);

            if ($e instanceof AllocationException) {
                return $this->travelAllocationService->createResponse2($alloctionRequest, $e);
            }

            $this->tracker->saveResult(ProcessTrackerAllocationEnum::EXCEPTION_RAISED, summary: "Exception Occurred in Lead Allocation with error : {$e->getMessage()}");

            return [
                'advisorId' => 0,
                'message' => $e->getMessage(),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ];
        }
    }

    private function fetchAvailableAdvisor(TravelQuote $lead)
    {
        return $this->travelAllocationService->fetchAvailableAdvisor(teamId: $this->teamId, lead: $lead, tracker: $this->tracker);
    }

    private function assignLead(TravelQuote $lead, User $advisor)
    {
        DB::beginTransaction();
        try {
            $this->travelAllocationService->assignLead($lead, $advisor, AssignmentTypeEnum::SYSTEM_ASSIGNED, tracker: $this->tracker);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            LoggerService::error($e->getMessage());

            $this->tracker->saveResult(ProcessTrackerAllocationEnum::EXCEPTION_RAISED, summary: "Assign Lead Failed with error : {$e->getMessage()}");
        }
    }
}
