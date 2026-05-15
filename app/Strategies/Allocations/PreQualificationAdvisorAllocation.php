<?php

declare(strict_types=1);

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypes;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Pipes\Allocation\Pqa\AssignLeadPipe;
use App\Pipes\Allocation\Pqa\FetchAvailablePreQualificationAdvisorPipe;
use App\Pipes\Allocation\Pqa\FetchLeadPipe;
use App\Pipes\Allocation\Pqa\MakeResponsePipe;
use App\Pipes\Allocation\Pqa\VerifyConcurrentAllocationPipe;
use App\Pipes\Allocation\Pqa\VerifyLeadPreChecksPipe;
use App\Services\Logger\LoggerService;
use App\Services\PqaAllocation\PqaAllocationService;
use Exception;
use Illuminate\Support\Facades\Pipeline;

class PreQualificationAdvisorAllocation implements Allocation
{
    public function __construct(
        protected string $uuid,
        protected bool $overrideAdvisorId = false,
    ) {}

    public function execute(): array
    {
        LoggerService::info(self::class.' - starting PQA allocation', extra: [
            'uuid' => $this->uuid,
            'overrideAdvisorId' => $this->overrideAdvisorId,
        ]);

        $allocationRequest = new AllocationRequest(
            quoteType: QuoteTypes::GROUP_MEDICAL,
            quoteUUID: $this->uuid,
            overrideAdvisorId: $this->overrideAdvisorId,
        );

        try {
            return Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                VerifyLeadPreChecksPipe::class,
                VerifyConcurrentAllocationPipe::class,
                FetchAvailablePreQualificationAdvisorPipe::class,
                AssignLeadPipe::class,
                MakeResponsePipe::class,
            ])->thenReturn();
        } catch (Exception $e) {
            LoggerService::warning(self::class.' - PQA allocation pipeline exception', extra: [
                'uuid' => $this->uuid,
                'exception' => $e->getMessage(),
            ]);

            return app(PqaAllocationService::class)->resolveAllocationResponse($allocationRequest, $e);
        }
    }
}
