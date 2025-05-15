<?php

namespace App\Pipes\Allocation\Car;

use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\CarAllocationService;
use App\Services\Logger\LoggerService;
use Closure;
use Illuminate\Database\Eloquent\Collection;

class FetchAvailableAdvisorPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $lead = $this->allocationRequest->getLead();
        $tier = $this->allocationRequest->get('tier');
        $teamId = $this->allocationRequest->getTeamId();

        // Find available users based on tier and lead source
        $availableUsers = $this->findAvailableUsers($tier, $lead->source, $lead, $teamId);

        // Find applicable rules for the lead
        $rules = $this->findRules($lead);

        // Determine the final advisor ID
        $carAllocationService = app(CarAllocationService::class);
        $advisorId = $carAllocationService->determineFinalUserId($lead, $availableUsers, $rules, $teamId, $tier);

        if (empty($advisorId) || $advisorId == 0) {
            LoggerService::info('Advisor not found. Skipping allocation.');
            $this->allocationRequest->markAsFailed();
            $this->throw('Advisor not found', self::OK);
        }

        // If advisor is same as previous, mark and skip
        if (! empty($advisorId) && $advisorId == $lead->advisor_id) {
            LoggerService::info('Advisor is same as previous advisor. Skipping allocation.');
            $this->allocationRequest->markAsSameAdvisor();
            $this->allocationRequest->setAdvisor(User::find($advisorId));

            return $next($request);
        }

        // Set the advisor in the allocation request
        $this->allocationRequest->setAdvisor(User::find($advisorId));

        return $next($request);
    }

    private function findAvailableUsers($tier, $leadSource, $lead, $teamId): array|Collection
    {
        $carAllocationService = app(CarAllocationService::class);

        return $carAllocationService->getEligibleUserForAllocation($tier, null, false, $leadSource, $teamId, $lead);
    }

    private function findRules($lead)
    {
        $carAllocationService = app(CarAllocationService::class);

        return $carAllocationService->getRules($lead);
    }
}
