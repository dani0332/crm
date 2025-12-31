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
use App\Pipes\Allocation\Health\AssignLeadPipe;
use App\Pipes\Allocation\Health\AssignTeamPipe;
use App\Pipes\Allocation\Health\FetchAvailableAdvisorPipe;
use App\Pipes\Allocation\Health\VerifyLeadPreChecksPipe;
use App\Services\AllocationService;
use Exception;
use Illuminate\Support\Facades\Pipeline;

class HealthAllocation implements Allocation
{
    public function __construct(protected $uuid, protected $teamId = false, protected bool $overrideAdvisorId = false) {}

    public function execute()
    {
        $allocationRequest = new AllocationRequest(
            quoteType: QuoteTypes::HEALTH,
            quoteUUID: $this->uuid,
            teamId: $this->teamId,
            overrideAdvisorId: $this->overrideAdvisorId
        );

        try {
            return Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                VerifyLeadPreChecksPipe::class,
                VerifyAlreadyInProgressAllocationPipe::class,
                ValidateNationalityConfigPipe::class,
                AssignTeamPipe::class,
                ApplyRuleExclusionPipe::class,
                FetchAvailableAdvisorPipe::class,
                ResetNationalityConfigPipe::class,
                FetchAvailableAdvisorPipe::class,
                AssignLeadPipe::class,
                MakeResponsePipe::class,
            ])->thenReturn();
        } catch (Exception $e) {
            return app(AllocationService::class)->resolveAllocationResponse($allocationRequest, $e);
        }
    }
}
