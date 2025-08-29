<?php

namespace App\Pipes\Allocation\Claim;

use App\Models\User;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FinalizeEligibleAdvisorPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);
       
        $eligibleAdvisors = $request->get('eligibleAdvisors');
        if(empty($eligibleAdvisors)){
            $this->throw('No eligible advisors found', self::OK);
        }
        $availableUserIds = collect($eligibleAdvisors ?? [])->pluck('user_id')->filter()->toArray();
        $availableUserIds = [];
  
        LoggerService::info('Available User IDs are: '.json_encode($availableUserIds));

        $advisorId = $this->getFinalAdvisorId($availableUserIds);
        $advisor = User::find($advisorId);
        

        if (empty($advisor)) {
            LoggerService::warning('No advisor found');
            $this->allocationRequest->markAsFailed();
            $this->throw('Advisor not found', self::OK);
        }

        $this->allocationRequest->setAdvisor($advisor);

        $this->verifyIfAdvisorIsSameAsPreviousAdvisor($advisor);

        return $next($request);
    }

    private function getFinalAdvisorId($finalEligibleUserIds)
    {
        return $finalEligibleUserIds[0] ?? 0;
    }

}
