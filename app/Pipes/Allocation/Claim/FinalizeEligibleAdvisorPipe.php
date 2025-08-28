<?php

namespace App\Pipes\Allocation\Claim;

use App\Models\BuyLeadRequest;
use App\Models\CarQuote;
use App\Models\LeadAllocation;

use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;
use App\Pipes\Allocation\Claim\BaseAllocationPipe;
use App\Models\User;

class FinalizeEligibleAdvisorPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $eligibleAdvisors = $request->get('eligibleAdvisors');

        $availableUserIds = collect($eligibleAdvisors)->pluck('user_id')->toArray();
        LoggerService::info('Available User IDs are: '.json_encode($availableUserIds));

        

        $advisorId = $this->getFinalAdvisorId($availableUserIds);
        $advisor = User::find($advisorId);

        if (! $advisor) {
            LoggerService::warning('No advisor found');

            $this->claimAssignmentRequest->markAsFailed();

            $this->throw('Advisor not found', self::OK);
        }

        $this->claimAssignmentRequest->setAdvisor($advisor);

        $this->verifyIfAdvisorIsSameAsPreviousAdvisor($advisor);

        return $next($request);
    }

    private function getFinalAdvisorId($finalEligibleUserIds)
    {
        // Return the first user ID from the final eligible user IDs if any, otherwise return 0.
        return count($finalEligibleUserIds) > 0 ? reset($finalEligibleUserIds) : 0;
    }

}