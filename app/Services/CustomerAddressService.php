<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Http\Requests\CustomerAddressRequest;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerAddressService
{
    public function createOrUpdateCustomerAddress(array $address, int $customerId, $quoteUuid)
    {
        if (! empty(array_filter((array) $address))) {
            $address = [
                'customer_id' => $customerId,
                'address_type' => $address['address_type'],
                'quote_type_id' => QuoteTypes::CAR->id(),
                'quote_uuid' => $quoteUuid,
                'office_number' => $address['villa_apartment_office_no'],
                'floor_number' => $address['floor_no'],
                'building_name' => $address['villa_building_name'],
                'street' => $address['street_name'],
                'area' => $address['area'],
                'city' => $address['city'],
                'landmark' => $address['landmark'],
                'is_default' => $address['address_type'] == 'Home' ? 1 : 0,
            ];
            $this->createOrUpdateAddress($address);
        }
    }

    public function createOrUpdateAddress(array $address)
    {
        info('Attempting to save CustomerAddress:', [
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
                    info(
                        'CustomerAddress updated successfully:',
                        ['customer_address_id' => $customerAddress->id],
                        ['quote_uuid' => $customerAddress->quote_uuid],
                    );
                } else {
                    info(
                        'No changes detected in CustomerAddress:',
                        ['customer_address_id' => $customerAddress->id],
                        ['quote_uuid' => $customerAddress->quote_uuid],
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
                info(
                    'CustomerAddress created successfully:',
                    ['customer_address_id' => $customerAddress->id],
                    ['quote_uuid' => $customerAddress->quote_uuid],
                );
            }
        } catch (\Exception $e) {
            // Log the error if something goes wrong
            info('Error saving CustomerAddress:', [
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
}
