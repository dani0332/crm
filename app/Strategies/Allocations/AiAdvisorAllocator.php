<?php

namespace App\Strategies\Allocations;

use App\Enums\QuoteTypes;
use App\Models\User;
use App\Pipes\Allocation\Common\FetchLeadPipe;
use App\Pipes\Allocation\Common\MakeResponsePipe;
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
            return false;
        }

        $advisor = self::tryAssignment($quoteType, $uuid);

        if (! $advisor) {
            return false;
        }

        return self::runProcess($quoteType, $uuid, $advisor, $assignPipe);
    }

    private static function runProcess(QuoteTypes $quoteType, string $uuid, User $advisor, $assignPipe)
    {
        $allocationRequest = new AllocationRequest(
            quoteType: $quoteType,
            quoteUUID: $uuid,
            overrideAdvisorId: true
        );

        $allocationRequest->set('advisor', $advisor);
        $allocationRequest->set('isAIAdvisor', true);

        try {
            return Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                $assignPipe,
                MakeResponsePipe::class,
            ])->thenReturn();
        } catch (Exception $e) {
            return [
                'advisorId' => 0,
                'message' => $e->getMessage(),
                'status' => $e->getCode(),
            ];
        }
    }

    private static function tryAssignment(QuoteTypes $quoteType, string $uuid): ?User
    {
        $lead = $quoteType->model()->where('uuid', $uuid)->first();

        if (! $lead) {
            return null;
        }

        if ($lead->isAIAdviserRequired()) {
            if ($lead->isAIAdvisorAssigned()) {

            } else {
                $lead->assignToAIAdvisor();
            }

            $lead->refresh();

            return $lead->advisor;
        } else {
            $lead->unAssignAIAdvisor();
        }

        return null;
    }
}
