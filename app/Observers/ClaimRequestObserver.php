<?php

namespace App\Observers;

use App\Models\ClaimRequest;
use App\Services\EmailServices\ClaimEmailService;
use App\Services\Logger\LoggerService;
use Exception;

class ClaimRequestObserver
{
    /**
     * Handle the BikeQuote "updated" event.
     */
    public function updated(ClaimRequest $claimRequest): void
    {
        $dirty = $claimRequest->getDirty();
        if (isset($dirty['manager_id'])) {
            try {
                app(ClaimEmailService::class)->sendIntroEmail($claimRequest);
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
