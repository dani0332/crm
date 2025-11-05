<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypes;
use App\Pipes\Allocation\Common\FetchLeadPipe;
use App\Pipes\Allocation\Common\MakeResponsePipe;
use App\Pipes\Allocation\Common\VerifyAlreadyInProgressAllocationPipe;
use App\Pipes\Allocation\Cyber\AssignLeadPipe;
use App\Pipes\Allocation\Cyber\FetchAvailableAdvisorPipe;
use App\Pipes\Allocation\Cyber\VerifyLeadPreChecksPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\AllocationService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Pipeline;

class CyberAllocation implements Allocation
{
    public function __construct(
        protected $uuid,
        protected $teamId = false,
        protected bool $overrideAdvisorId = false
    ) {}

    public function execute()
    {
        LoggerService::info(self::class.' - Starting Cyber lead allocation', extra: [
            'uuid' => $this->uuid,
            'teamId' => $this->teamId,
            'overrideAdvisorId' => $this->overrideAdvisorId,
        ]);

        $allocationRequest = new AllocationRequest(
            quoteType: QuoteTypes::CYBER,
            quoteUUID: $this->uuid,
            teamId: $this->teamId,
            overrideAdvisorId: $this->overrideAdvisorId
        );

        try {
            LoggerService::info(self::class.' - Sending allocation request through pipeline');

            return Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                VerifyLeadPreChecksPipe::class,
                VerifyAlreadyInProgressAllocationPipe::class,
                FetchAvailableAdvisorPipe::class,
                AssignLeadPipe::class,
                MakeResponsePipe::class,
            ])->thenReturn();

        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred in Cyber allocation pipeline', extra: [
                'uuid' => $this->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return app(AllocationService::class)->resolveAllocationResponse($allocationRequest, $e);
        }
    }
}













