<?php

declare(strict_types=1);

namespace App\Services\ClaimAllocation;


use Exception;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\Logger\LoggerService;
use App\Enums\AssignmentTypeEnum;
use Illuminate\Support\Facades\Pipeline;
use App\Pipes\Allocation\Claim\FetchLeadPipe;
use App\Pipes\Allocation\Claim\VerifyAlreadyInProgressAllocationPipe;
use App\Pipes\Allocation\Claim\FinalizeEligibleAdvisorPipe;
use App\Enums\QuoteTypes;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Pipes\Allocation\Claim\AssignLeadPipe;
use App\Pipes\Allocation\Claim\MakeResponsePipe;
use Illuminate\Http\Response;
use App\Models\ClaimRequest;

class ClaimAllocationService 
{
   

    public function execute(string $quoteUuid, int $quoteTypeId)
    {
        LoggerService::startQuoteLogging($quoteUuid, LoggerFeatureEnum::CLAIM_ALLOCATION);

        $allocationRequest = new AllocationRequest(
            quoteType: QuoteTypes::from($quoteTypeId),
            quoteUUID:  $quoteUuid,
            assignmentType: AssignmentTypeEnum::SYSTEM_REASSIGNED,
            isReassignmentJob: false,
        );

        try {
            Pipeline::send($allocationRequest)->through([
                FetchLeadPipe::class,
                VerifyAlreadyInProgressAllocationPipe::class,
                FinalizeEligibleAdvisorPipe::class,
                AssignLeadPipe::class,
                MakeResponsePipe::class,
            ])->thenReturn();
        } catch (Exception $e) {
            $this->resolveAllocationResponse($allocationRequest, $e);
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

    
    public function leadAllocationFailed(string $uuid, QuoteTypes $quoteType)
    {
        $quote = ClaimRequest::where('uuid', $uuid)->first();

        if ($quote) {
            $quote->markLeadAllocationFailed();
        }
        return false;
    }

}
