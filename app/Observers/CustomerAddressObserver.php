<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Log;

class CustomerAddressObserver
{
    public function updated(CustomerAddress $customerAddress)
    {
        Log::info('CustomerAddressObserver@updated');
        try {
            // Check if any attributes have been modified
            $dirty = $customerAddress->getDirty();

            if (! empty($dirty)) {
                // Fetch the associated car quote using quote_uuid
                $carQuote = getCarQuoteByUuid($customerAddress->quote_uuid);

                if ($carQuote) {
                    // Check if the car quote status is 'PolicyIssued'
                    if ($carQuote->quote_status_id === QuoteStatusEnum::PolicyIssued) {
                        // Dispatch the job to sync courier quote with MACRM
                        SyncCourierQuoteWithMacrm::dispatch($carQuote, QuoteTypeId::Car);
                    }
                }
            }
        } catch (\Exception $e) {
            // Log any exception that occurs
            Log::error('An error occurred in CustomerAddressObserver@updated', [
                'error' => $e->getMessage(),
                'customerAddressId' => $customerAddress->id,
                'quote_uuid' => $customerAddress->quote_uuid,
            ]);
        }
    }
}
