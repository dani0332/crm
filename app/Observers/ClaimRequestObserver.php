<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\ClaimRequest;
use App\Services\Logger\LoggerService;
use Exception;
use App\Services\EmailServices\ClaimEmailService;

class ClaimRequestObserver
{
    /**
     * Handle the BikeQuote "updated" event.
     */
    public function updated(ClaimRequest $claimRequest): void
    {
        $dirty = $claimRequest->getDirty();
        if (isset($dirty['advisor_id'])) {
            try {
                $claimRequest->markLeadAllocationPassed();

                app(ClaimEmailService::class)->sendIntroEmail($claimRequest);
                
            } catch (Exception $e) {
                LoggerService::error('ClaimRequestObserver - handle claim  update advisor failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $claimRequest->uuid,
                ]);
            }
        }
    }
}
