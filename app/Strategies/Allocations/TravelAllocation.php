<?php

namespace App\Strategies\Allocations;

use App\Enums\ProcessTracker\StepsEnums\ProcessTrackerAllocationEnum;
use App\Enums\QuoteTypes;
use App\Exceptions\Allocation\AllocationException;
use App\Pipelines\Allocation\Common\FetchLeadPipeline;
use App\Pipelines\Allocation\Common\MakeResponsePipeline;
use App\Pipelines\Allocation\Common\VerifyAlreadyInProgressAllocationPipeline;
use App\Pipelines\Allocation\Travel\AssignChildLeadPipeline;
use App\Pipelines\Allocation\Travel\AssignLeadPipeline;
use App\Pipelines\Allocation\Travel\FetchAvailableAdvisorPipeline;
use App\Pipelines\Allocation\Travel\VerifyLeadPreChecksPipeline;
use App\Services\AllocationService;
use App\Strategies\Allocations\PipelineHandlers\AllocationRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Pipeline;
use Throwable;

class TravelAllocation extends AllocationService implements Allocation
{
    public $allocationId;
    public $teamId;
    private bool $overrideAdvisorId = false;

    public function __construct($allocationId, $teamId = false, bool $overrideAdvisorId = false)
    {
        $this->allocationId = $allocationId;
        $this->teamId = $teamId;
        $this->overrideAdvisorId = $overrideAdvisorId;
    }

    public function executeSteps()
    {
        $alloctionRequest = new AllocationRequest(
            quoteType: QuoteTypes::TRAVEL,
            quoteUUID: $this->allocationId,
            teamId: $this->teamId,
            overrideAdvisorId: $this->overrideAdvisorId
        );

        try {

            return Pipeline::send($alloctionRequest)->through([
                FetchLeadPipeline::class,
                VerifyLeadPreChecksPipeline::class,
                VerifyAlreadyInProgressAllocationPipeline::class,
                FetchAvailableAdvisorPipeline::class,
                AssignLeadPipeline::class,
                AssignChildLeadPipeline::class,
                MakeResponsePipeline::class,
            ])->thenReturn();

        } catch (AllocationException|Throwable $e) {
            $this->leadAllocationFailed($this->allocationId, QuoteTypes::TRAVEL);

            if ($e instanceof AllocationException) {
                return $this->createResponse2($alloctionRequest, $e);
            }

            $this->tracker->saveResult(ProcessTrackerAllocationEnum::EXCEPTION_RAISED, summary: "Exception Occurred in Lead Allocation with error : {$e->getMessage()}");

            return [
                'advisorId' => 0,
                'message' => $e->getMessage(),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ];
        }
    }
}
