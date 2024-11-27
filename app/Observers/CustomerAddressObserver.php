<?php

namespace App\Observers;

use App\Enums\BirdFlowStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Models\CustomerAddress;
use App\Services\CarQuoteService;
use Illuminate\Support\Facades\Log;

class CustomerAddressObserver
{
    public function updated(CustomerAddress $customerAddress)
    {
        Log::info('CustomerAddressObserver@updated for quote_uuid: '.$customerAddress->quote_uuid);
        try {
            // Check if any attributes have been modified
            $dirty = $customerAddress->getDirty();

            if (! empty($dirty)) {
                // Fetch the associated car quote using quote_uuid
                $carQuote = getCarQuoteByUuid($customerAddress->quote_uuid);

                if ($carQuote) {
                    // Check if the car quote status is 'PolicyIssued'
                    if (in_array($carQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])) {
                        // Dispatch the job to sync courier quote with MACRM
                        SyncCourierQuoteWithMacrm::dispatch($carQuote, QuoteTypeId::Car);
                    }
                    if ($customerAddress) {
                        $address = [
                            'address_type' => $customerAddress->type,
                            'villa_apartment_office_no' => $customerAddress->office_number,
                            'floor_no' => $customerAddress->floor_number,
                            'villa_building_name' => $customerAddress->building_name,
                            'street_name' => $customerAddress->street,
                            'area' => $customerAddress->area,
                            'city' => $customerAddress->city,
                            'landmark' => $customerAddress->landmark,
                        ];
                        info('Sending address notification to customer for lead in CustomerAddressObserver : '.$carQuote->uuid);
                        app(CarQuoteService::class)->triggerBirdFlow($carQuote, $address, BirdFlowStatusEnum::ADDRESS_UPDATED);
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
