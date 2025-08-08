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

        [
            'advisor' => $advisor,
            'isAdvisorAlreadyAssigned' => $isAdvisorAlreadyAssigned,
        ] = self::tryAssignment($quoteType, $uuid);

        if (! $advisor) {
            return false;
        }

        return self::runAIProcess($quoteType, $uuid, $advisor, $assignPipe, $isAdvisorAlreadyAssigned);
    }

    private static function runAIProcess(QuoteTypes $quoteType, string $uuid, User $advisor, $assignPipe, bool $isAdvisorAlreadyAssigned)
    {
        $allocationRequest = new AllocationRequest(
            quoteType: $quoteType,
            quoteUUID: $uuid,
            overrideAdvisorId: true
        );

        $allocationRequest->setAdvisor($advisor);
        $allocationRequest->set('isAdvisorAlreadyAssigned', $isAdvisorAlreadyAssigned);

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

    private static function tryAssignment(QuoteTypes $quoteType, string $uuid): ?array
    {
        $lead = $quoteType->model()->where('uuid', $uuid)->first();

        if (! $lead) {
            return null;
        }

        $data = [];

        if ($lead->isAIAdviserRequired()) {
            $data['isAdvisorAlreadyAssigned'] = $lead->isAIAdvisorAssigned();
            $data['advisor'] = User::getAiAdvisor();

            return $data;
        }

        return null;
    }
}
