<?php

namespace App\Observers;

use App\Enums\BirdFlowStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Models\CustomerAddress;
use App\Services\CustomerAddressService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Facades\Log;

class CustomerAddressObserver
{
    use GenericQueriesAllLobs;

    public function updated(CustomerAddress $customerAddress)
    {
        Log::info('CustomerAddressObserver@updated for quote_uuid: '.$customerAddress->quote_uuid);
        try {
            // Check if any attributes have been modified
            $dirty = $customerAddress->getDirty();

            if (! empty($dirty)) {
                // Fetch the associated car/cyber quote using quote_uuid
                if (in_array($customerAddress->quote_type_id, [QuoteTypeId::Car, QuoteTypeId::Cyber])) {

                    $modelType = QuoteTypeId::getOptions()[$customerAddress->quote_type_id] ?? null;
                    $quote = $this->getQuoteObject($modelType, $customerAddress->quote_uuid);
                    if ($quote) {
                        // Check if the car/cyber quote status is 'PolicyIssued'
                        if (in_array($quote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])) {
                            // Dispatch the job to sync courier quote with MACRM
                            SyncCourierQuoteWithMacrm::dispatch($quote, $customerAddress->quote_type_id);
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
                            info('Sending address notification to customer for lead in CustomerAddressObserver : '.$quote->uuid);
                            app(CustomerAddressService::class)->triggerBirdFlow($quote, $address, BirdFlowStatusEnum::ADDRESS_UPDATED, $customerAddress->quote_type_id);
                        }
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
