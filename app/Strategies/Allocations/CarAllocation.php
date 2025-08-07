<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypes;
use App\Pipes\Allocation\Car\ApplyRuleExclusionPipe;
use App\Pipes\Allocation\Car\AssignLeadPipe;
use App\Pipes\Allocation\Car\EvaluateTeamPipe;
use App\Pipes\Allocation\Car\EvaluateTierPipe;
use App\Pipes\Allocation\Car\FetchEligibleAdvisorsPipe;
use App\Pipes\Allocation\Car\FetchTierUsersPipe;
use App\Pipes\Allocation\Car\FinalizeEligibleAdvisorPipe;
use App\Pipes\Allocation\Car\VerifyLeadPreChecksPipe;
use App\Pipes\Allocation\Common\FetchLeadPipe;
use App\Pipes\Allocation\Common\MakeResponsePipe;
use App\Pipes\Allocation\Common\ResetNationalityConfigPipe;
use App\Pipes\Allocation\Common\ValidateNationalityConfigPipe;
use App\Pipes\Allocation\Common\VerifyAlreadyInProgressAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\AllocationService;
use Exception;
use Illuminate\Support\Facades\Pipeline;

class CarAllocation implements Allocation
{
    public function __construct(
        protected $uuid,
        protected $teamId = null,
        protected bool $evaluateTierOnly = false,
        protected bool $overrideAdvisorId = false,
        protected bool $sicAdvisorRequested = false
    ) {}

    public function execute()
    {
        if ($response = AiAdvisorAllocator::try(QuoteTypes::CAR, $this->uuid, AssignLeadPipe::class, $this->evaluateTierOnly)) {
            return $response;
        }

        $allocationRequest = new AllocationRequest(
            quoteType: QuoteTypes::CAR,
            quoteUUID: $this->uuid,
            teamId: $this->teamId,
            overrideAdvisorId: $this->overrideAdvisorId,
            evaluateTierOnly: $this->evaluateTierOnly
        );

        try {
            return Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                VerifyLeadPreChecksPipe::class,
                VerifyAlreadyInProgressAllocationPipe::class,
                EvaluateTierPipe::class,
                ValidateNationalityConfigPipe::class,
                EvaluateTeamPipe::class,
                FetchTierUsersPipe::class,
                ApplyRuleExclusionPipe::class,
                FetchEligibleAdvisorsPipe::class,
                ResetNationalityConfigPipe::class,
                ApplyRuleExclusionPipe::class,
                FetchEligibleAdvisorsPipe::class,
                FinalizeEligibleAdvisorPipe::class,
                AssignLeadPipe::class,
                MakeResponsePipe::class,
            ])->thenReturn();

        } catch (Exception $e) {
            return app(AllocationService::class)->resolveAllocationResponse($allocationRequest, $e);
        }
    }
}
