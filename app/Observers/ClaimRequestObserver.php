<?php

namespace App\Observers;

use App\Models\ClaimRequest;
use App\Services\Logger\LoggerService;
use Exception;
use App\Jobs\Claim\ClaimIntroEmail;
use App\Enums\LeadSourceEnum;

class ClaimRequestObserver
{
    /**
     * Handle the BikeQuote "updated" event.
     */
    public function updated(ClaimRequest $claimRequest): void
    {
        $dirty = $claimRequest->getDirty();
        if (isset($dirty['manager_id'])) {
            if($claimRequest->source == LeadSourceEnum::IMCRM) {
                LoggerService::info("ClaimRequestObserver - handle claim  update manager - source is not IMCRM - skipping intro email");
                return;
            }
            try {
                ClaimIntroEmail::dispatch($claimRequest->uuid)->delay(now()->addSeconds(10));
                LoggerService::info("ClaimIntroEmail dispatched for claim: ".$claimRequest->uuid);
                
            } catch (Exception $e) {
                LoggerService::warning('ClaimRequestObserver - handle claim  update manager failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $claimRequest->uuid,
                    'manager_id' => $claimRequest->manager_id,
                ]);
            }
        }
    }
}
