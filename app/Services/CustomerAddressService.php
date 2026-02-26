<?php

namespace App\Services;

use App\Enums\BirdFlowStatusEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypes;
use App\Facades\Ken;
use App\Http\Requests\CustomerAddressRequest;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Models\CustomerAdditionalContact;
use App\Models\CustomerAddress;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerAddressService
{
    public function createOrUpdateCustomerAddress(array $address, int $customerId, $quoteUuid, $quoteTypeId = null)
    {
        $addressType = $address['address_type'] ?? null;
        if (! empty(array_filter((array) $address))) {
            $address = [
                'customer_id' => $customerId,
                'address_type' => $addressType,
                'quote_type_id' => $quoteTypeId ?? QuoteTypes::CAR->id(),
                'quote_uuid' => $quoteUuid,
                'office_number' => $address['villa_apartment_office_no'] ?? null,
                'floor_number' => $address['floor_no'] ?? null,
                'building_name' => $address['villa_building_name'] ?? null,
                'street' => $address['street_name'] ?? null,
                'area' => $address['area'] ?? null,
                'city' => $address['city'] ?? null,
                'landmark' => $address['landmark'] ?? null,
                'is_default' => $addressType == 'Home' ? 1 : 0,
            ];
            $this->createOrUpdateAddress($address);
        }
    }

    public function syncCustomerAddress(Request $request, QuoteTypes $quoteType, $quote, string $email)
    {
        $this->validateAddress($request);

        $customerId = app(CustomerService::class)->getCustomerIdByEmail($email);
        $addressObj = $request->input('addressObj', []);
        $addressType = $addressObj['address_type'] ?? null;
        if (empty($quote) || empty($customerId) || ! in_array($addressType, ['Home', 'Office'], true) || empty(array_filter((array) $addressObj))) {
            return;
        }

        $this->sendAddressNotificationToCustomer($quote, $addressObj, $quoteType->id());
        $this->createOrUpdateCustomerAddress($addressObj, $customerId, $quote->uuid, $quoteType->id());
        SyncCourierQuoteWithMacrm::dispatch($quote, $quoteType->id());
    }

    public function createOrUpdateAddress(array $address)
    {
        LoggerService::info('Attempting to save CustomerAddress:', [
            'customer_id' => $address['customer_id'],
            'quote_uuid' => $address['quote_uuid'],
        ]);

        try {
            // Find the existing record by customer_id and quote_uuid
            $customerAddress = CustomerAddress::where([
                'customer_id' => $address['customer_id'],
                'quote_uuid' => $address['quote_uuid'],
            ])->first();

            if ($customerAddress) {
                // Fill the model with new data
                $customerAddress->fill([
                    'type' => $address['address_type'],
                    'quote_type_id' => $address['quote_type_id'],
                    'office_number' => $address['office_number'],
                    'floor_number' => $address['floor_number'],
                    'building_name' => $address['building_name'],
                    'street' => $address['street'],
                    'area' => $address['area'],
                    'city' => $address['city'],
                    'landmark' => $address['landmark'],
                    'is_default' => $address['is_default'],
                ]);

                // Check if any fields are dirty (modified)
                if ($customerAddress->isDirty()) {
                    $customerAddress->save(); // Save only if changes exist
                    LoggerService::info(
                        'CustomerAddress updated successfully:',
                        ['customer_address_id' => $customerAddress->id, 'quote_uuid' => $customerAddress->quote_uuid]
                    );
                } else {
                    LoggerService::info(
                        'No changes detected in CustomerAddress:',
                        ['customer_address_id' => $customerAddress->id, 'quote_uuid' => $customerAddress->quote_uuid]
                    );
                }
            } else {
                // If no record exists, create a new one
                $customerAddress = CustomerAddress::create([
                    'customer_id' => $address['customer_id'],
                    'quote_uuid' => $address['quote_uuid'],
                    'type' => $address['address_type'],
                    'quote_type_id' => $address['quote_type_id'],
                    'office_number' => $address['office_number'],
                    'floor_number' => $address['floor_number'],
                    'building_name' => $address['building_name'],
                    'street' => $address['street'],
                    'area' => $address['area'],
                    'city' => $address['city'],
                    'landmark' => $address['landmark'],
                    'is_default' => $address['is_default'],
                ]);
                LoggerService::info(
                    'CustomerAddress created successfully:',
                    ['customer_address_id' => $customerAddress->id, 'quote_uuid' => $customerAddress->quote_uuid]
                );
            }
        } catch (\Exception $e) {
            // Log the error if something goes wrong
            LoggerService::error('Error saving CustomerAddress:', [
                'customer_id' => $address['customer_id'],
                'quote_uuid' => $address['quote_uuid'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function fetchFormattedAddress($address)
    {
        $addressOrder = [
            'villa_apartment_office_no',
            'floor_no',
            'villa_building_name',
            'street_name',
            'area',
            'city',
            'landmark',
        ];

        return implode(', ', array_filter(array_map(
            fn ($key) => $address[$key] ?? null,
            $addressOrder
        )));
    }

    public function fetchFullAddress($address)
    {
        $addressOrder = [
            'office_number',
            'floor_number',
            'building_name',
            'street',
            'area',
            'city',
            'landmark',
        ];

        return implode(', ', array_filter(array_map(
            fn ($key) => $address[$key] ?? null,
            $addressOrder
        )));
    }

    public function validateAddress(Request $request)
    {
        $addressRequest = CustomerAddressRequest::createFrom($request);

        // Manually validate the request
        $validator = Validator::make($addressRequest->all(), $addressRequest->rules());

        if ($validator->fails()) {
            // Handle validation errors
            throw new ValidationException($validator);
        }

        return true; // Validation passed
    }

    public function sendAddressNotificationToCustomer($lead, $address, $quoteTypeId)
    {
        LoggerService::info('Checking for sending courier notification : '.$lead->uuid);

        // Use a different variable name for the result of the query
        $existingAddress = CustomerAddress::where([
            'quote_uuid' => $lead->uuid,
            'customer_id' => $lead->customer_id,
        ])->first();

        if (empty($existingAddress?->type)) {
            LoggerService::info('Sending address notification to customer for lead : '.$lead->uuid);
            // only trigger bird flow if address is not already added
            $this->triggerBirdFlow($lead, $address, BirdFlowStatusEnum::ADDRESS_ADDED, $quoteTypeId);
        }
    }

    public function triggerBirdFlow($lead, $address, $actionType, $quoteTypeId)
    {
        if (! $lead->embeddedTransactions()->exists()) {
            LoggerService::info('No embedded transactions found for lead : '.$lead->uuid);

            return;
        }

        LoggerService::info('Checking for courier transaction for lead : '.$lead->uuid);
        $courierEmbeddedTransaction = $lead->embeddedTransactions
            ->filter(function ($transaction) {
                return $transaction->product?->embeddedProduct?->short_code === EmbeddedProductEnum::COURIER;
            });

        if (
            ($selectedTransaction = $courierEmbeddedTransaction?->firstWhere('is_selected', 1)) &&
            in_array($selectedTransaction?->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::AUTHORISED])
        ) {
            LoggerService::info('Triggering Bird Courier Flow for address notification for lead : '.$lead->uuid);
            $embeddedTransactionRefId = $courierEmbeddedTransaction->first()->code;
            $address = $this->fetchFormattedAddress($address);
            $customerAdditionalContact = CustomerAdditionalContact::select('value')
                ->firstWhere([
                    ['customer_id', $lead->customer_id],
                    ['key', 'alternate_mobile_no'],
                ]);
            $payload = [
                'quoteUID' => $lead->uuid,
                'quoteTypeId' => (int) $quoteTypeId,
                'actionType' => $actionType,
                'refId' => $embeddedTransactionRefId,
                'address' => $address,
                'alternateNumber' => $customerAdditionalContact->value ?? '',
            ];
            LoggerService::info('Payload for Bird Courier Flow : '.json_encode($payload));

            Ken::request('/trigger-bird-courier-flow', 'post', $payload);
        } else {
            LoggerService::info('Either No courier embedded transaction found or payment is not CAPTURED OR AUTHORISED for lead : '.$lead->uuid);
        }
    }
}
