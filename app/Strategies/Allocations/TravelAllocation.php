<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypes;
use App\Pipes\Allocation\Common\ApplyRuleExclusionPipe;
use App\Pipes\Allocation\Common\FetchLeadPipe;
use App\Pipes\Allocation\Common\MakeResponsePipe;
use App\Pipes\Allocation\Common\ResetNationalityConfigPipe;
use App\Pipes\Allocation\Common\ValidateNationalityConfigPipe;
use App\Pipes\Allocation\Common\VerifyAlreadyInProgressAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Pipes\Allocation\Travel\AssignChildLeadPipe;
use App\Pipes\Allocation\Travel\AssignLeadPipe;
use App\Pipes\Allocation\Travel\EvaluateTeamPipe;
use App\Pipes\Allocation\Travel\FetchAvailableAdvisorPipe;
use App\Pipes\Allocation\Travel\VerifyLeadPreChecksPipe;
use App\Services\AllocationService;
use Exception;
use Illuminate\Support\Facades\Pipeline;

class TravelAllocation implements Allocation
{
    public function __construct(protected $uuid, protected $teamId = false, protected bool $overrideAdvisorId = false) {}

    public function execute()
    {
        $allocationRequest = new AllocationRequest(
            quoteType: QuoteTypes::TRAVEL,
            quoteUUID: $this->uuid,
            teamId: $this->teamId,
            overrideAdvisorId: $this->overrideAdvisorId
        );

        try {

            return Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                VerifyLeadPreChecksPipe::class,
                VerifyAlreadyInProgressAllocationPipe::class,
                EvaluateTeamPipe::class,
                ValidateNationalityConfigPipe::class,
                ApplyRuleExclusionPipe::class,
                FetchAvailableAdvisorPipe::class,
                ResetNationalityConfigPipe::class,
                FetchAvailableAdvisorPipe::class,
                AssignLeadPipe::class,
                AssignChildLeadPipe::class,
                MakeResponsePipe::class,
            ])->thenReturn();

        } catch (Exception $e) {
            return app(AllocationService::class)->resolveAllocationResponse($allocationRequest, $e);
        }
    }
}
