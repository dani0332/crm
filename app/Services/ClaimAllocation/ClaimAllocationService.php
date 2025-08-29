<?php

declare(strict_types=1);

namespace App\Services\ClaimAllocation;

use App\Enums\AssignmentTypeEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Models\ClaimRequest;
use App\Pipes\Allocation\Claim\AssignLeadPipe;
use App\Pipes\Allocation\Claim\FetchLeadPipe;
use App\Pipes\Allocation\Claim\FinalizeEligibleAdvisorPipe;
use App\Pipes\Allocation\Claim\MakeResponsePipe;
use App\Pipes\Allocation\Claim\VerifyAlreadyInProgressAllocationPipe;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;
use App\Pipes\Allocation\Claim\FetchEligibleAdvisorsPipe;

class ClaimAllocationService
{
    public function execute(string $quoteUuid, int $quoteTypeId)
    {
        LoggerService::startQuoteLogging($quoteUuid, LoggerFeatureEnum::CLAIM_ALLOCATION);
        $quoteType = QuoteTypes::getName($quoteTypeId);

        $allocationRequest = new AllocationRequest(
            quoteType: $quoteType,
            quoteUUID: $quoteUuid,
            assignmentType: AssignmentTypeEnum::SYSTEM_REASSIGNED,
            isReassignmentJob: false,
        );

        try {
            $result = Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                // VerifyAlreadyInProgressAllocationPipe::class,
                FetchEligibleAdvisorsPipe::class,
                FinalizeEligibleAdvisorPipe::class,
                AssignLeadPipe::class,
                MakeResponsePipe::class,
            ])->thenReturn();

            return $result;
        } catch (Exception $e) {
 
            return $this->resolveAllocationResponse($allocationRequest, $e);
        }

    }

    public function resolveAllocationResponse(AllocationRequest $request, ?Exception $exception = null): array
    {

        if ($lead = $request->getLead()) {
            $lead->endAllocation();
        }

        if ($request->isAllocated() || $request->isSameAdvisor()) {
            $message = 'Advisor assigned successfully!';
          
            if ($request->isSameAdvisor()) {
                $message = 'Found same advisor as previous advisor so further allocation is skipped';
            }

            $data = [
                'advisorId' => $request->getAdvisor()?->id ?? $lead?->advisor_id,
                'message' => $message,
                'status' => Response::HTTP_OK,
            ];
        
            return $data;
        }

        if ($request->isFailed()) {
            $this->leadAllocationFailed($request->getQuoteUUID(), $request->getQuoteType());
        }

        return [
            'advisorId' => 0,
            'message' => $exception ? $exception->getMessage() : 'Lead allocation failed',
            'status' => $exception ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR,
        ];
    }

    public function leadAllocationFailedForClaim(string $uuid, QuoteTypes $quoteType)
    {
        $quote = ClaimRequest::where('uuid', $uuid)->first();

        if ($quote) {
            $quote->markLeadAllocationFailedForClaim();
        }

        return false;
    }

    public function syncClaimAllocationConfig(int $userId, object $data)
    {

        DB::table('claims_lead_allocation_config')->updateOrInsert(
            [
                'user_id' => $userId,
                'quote_type_id' => $data->quoteTypeId,
            ],
            [
                'max_capacity' => 100,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

}
