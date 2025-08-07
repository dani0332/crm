<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypes;
use App\Pipes\Allocation\Common\FetchLeadPipe;
use App\Pipes\Allocation\Common\MakeResponsePipe;
use App\Pipes\Allocation\Common\VerifyIfEligibleForAIAdvisorPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use Exception;
use Illuminate\Support\Facades\Pipeline;

class AiAdvisorAllocator
{
    public static function try(
        QuoteTypes $quoteType,
        string $uuid,
        $assignPipe,
        bool $skipAIAdvisor = false,
    ) {
        if ($skipAIAdvisor) {
            return;
        }

        $allocationRequest = new AllocationRequest(
            quoteType: $quoteType,
            quoteUUID: $uuid,
            overrideAdvisorId: true
        );

        try {
            return Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                VerifyIfEligibleForAIAdvisorPipe::class,
                $assignPipe,
                MakeResponsePipe::class,
            ])->thenReturn();
        } catch (Exception $e) {
            return null;
        }
    }
}
