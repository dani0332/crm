<?php

namespace App\Pipes\Allocation\Claim;

use App\Models\User;
use App\Pipes\Allocation\Handlers\Claim\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FinalizeEligibleManagerPipe extends BaseAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $eligibleManagers = $request->get('eligibleManagers');
        if (empty($eligibleManagers)) {
            $this->throw('No eligible managers found', self::OK);
        }
        $availableUserIds = collect($eligibleManagers ?? [])->pluck('user_id')->filter()->toArray();

        LoggerService::info('Available User IDs are: '.json_encode($availableUserIds));

        $managerId = $this->getFinalManagerId($availableUserIds);
        $manager = User::where('id', $managerId)->first();
        if (empty($manager)) {
            LoggerService::warning('No manager found');
            $this->allocationRequest->markAsFailed();
            $this->throw('Manager not found', self::OK);
        }

        $this->allocationRequest->setManager($manager);

        $this->verifyIfManagerIsSameAsPreviousManager($manager);

        return $next($request);
    }

    private function getFinalManagerId($finalEligibleUserIds)
    {
        return $finalEligibleUserIds[0] ?? 0;
    }

}
