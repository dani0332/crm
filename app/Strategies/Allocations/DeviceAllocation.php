<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypes;
use App\Exceptions\Allocation\AllocationException;
use App\Pipes\Allocation\Common\FetchLeadPipe;
use App\Pipes\Allocation\Common\MakeResponsePipe;
use App\Pipes\Allocation\Common\VerifyAlreadyInProgressAllocationPipe;
use App\Pipes\Allocation\Device\AssignLeadPipe;
use App\Pipes\Allocation\Device\EvaluateTeamPipe;
use App\Pipes\Allocation\Device\FetchAvailableAdvisorPipe;
use App\Pipes\Allocation\Device\VerifyLeadPreChecksPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\AllocationService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Pipeline;

class DeviceAllocation implements Allocation
{
    public const HAPPINESS_SUPPORT_USER_EMAIL = 'happiness@support.insurancemarket.ae';

    public function __construct(
        protected $uuid,
        protected $teamId = false,
        protected bool $overrideAdvisorId = false
    ) {}

    public function execute()
    {
        LoggerService::info(self::class.' - Starting Device lead allocation', extra: [
            'uuid' => $this->uuid,
            'teamId' => $this->teamId,
            'overrideAdvisorId' => $this->overrideAdvisorId,
        ]);

        $allocationRequest = new AllocationRequest(
            quoteType: QuoteTypes::DEVICE,
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
                EvaluateTeamPipe::class,
                FetchAvailableAdvisorPipe::class,
                AssignLeadPipe::class,
                MakeResponsePipe::class,
            ])->thenReturn();

        } catch (Exception $e) {
            $isExpectedStop = $e instanceof AllocationException && $e->getCode() === Response::HTTP_OK;

            if ($isExpectedStop) {
                LoggerService::info(self::class.' - Device allocation stopped (expected)', extra: [
                    'uuid' => $this->uuid,
                    'message' => $e->getMessage(),
                ]);
            }

            return app(AllocationService::class)->resolveAllocationResponse($allocationRequest, $e);
        }
    }
}
