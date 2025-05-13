<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypes;
use App\Exceptions\Allocation\AllocationException;
use App\Pipes\Allocation\Common\FetchLeadPipe;
use App\Pipes\Allocation\Common\MakeResponsePipe;
use App\Pipes\Allocation\Common\VerifyAlreadyInProgressAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Pipes\Allocation\Travel\AssignChildLeadPipe;
use App\Pipes\Allocation\Travel\AssignLeadPipe;
use App\Pipes\Allocation\Travel\FetchAvailableAdvisorPipe;
use App\Pipes\Allocation\Travel\VerifyLeadPreChecksPipe;
use App\Services\AllocationService;
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
                FetchLeadPipe::class,
                VerifyLeadPreChecksPipe::class,
                VerifyAlreadyInProgressAllocationPipe::class,
                FetchAvailableAdvisorPipe::class,
                AssignLeadPipe::class,
                AssignChildLeadPipe::class,
                MakeResponsePipe::class,
            ])->thenReturn();

        } catch (AllocationException|Throwable $e) {
            $this->leadAllocationFailed($this->allocationId, QuoteTypes::TRAVEL);

            if ($e instanceof AllocationException) {
                return $this->createResponse2($alloctionRequest, $e);
            }

            return [
                'advisorId' => 0,
                'message' => $e->getMessage(),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ];
        }
    }
}
