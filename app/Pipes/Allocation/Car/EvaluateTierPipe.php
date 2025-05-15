<?php

namespace App\Pipes\Allocation\Car;

use App\Enums\TiersEnum;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\CarAllocationService;
use App\Services\Logger\LoggerService;
use Closure;

class EvaluateTierPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $lead = $this->allocationRequest->getLead();

        // Find or determine the tier for the lead
        $tier = $this->determineTier($lead);

        if (! $tier) {
            LoggerService::info('Tier not found. Skipping allocation.');
            $this->allocationRequest->markAsFailed();
            $this->throw('Tier not found', self::OK);
        }

        // For Tier R, skip allocation
        if ($tier->name == TiersEnum::TIER_R) {
            LoggerService::info(self::class.' - Lead is Tier R. Skipping allocation');
            $this->allocationRequest->markAsFailed();
            $this->throw('Lead is Tier R. Skipping allocation.', self::OK);
        }

        // Save the tier ID to the lead
        $lead->tier_id = $tier->id;
        $lead->save();

        // Add the tier to the allocation request
        $this->allocationRequest->set('tier', $tier);

        return $next($request);
    }

    private function determineTier($lead)
    {
        $carAllocationService = app(CarAllocationService::class);

        $tier = $lead->tier_id != null
            ? $carAllocationService->getTier($lead->tier_id)
            : $carAllocationService->findTier($lead);

        if ($tier) {
            LoggerService::info('Checking if tier update is required');
            $updatedTierId = $carAllocationService->updateTierBeforeEligibleUserIdentification($lead);

            if (! empty($updatedTierId) && $updatedTierId != $lead->tier_id) {
                $lead->tier_id = $updatedTierId;
                $lead->save();
                $tier = $carAllocationService->getTier($updatedTierId);
            }
        }

        return $tier;
    }
}
