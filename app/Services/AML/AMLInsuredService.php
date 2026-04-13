<?php

namespace App\Services\AML;

use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Services\Logger\LoggerService;

class AMLInsuredService
{
    public function getInsuredDetails(?string $customerType, ?string $idType, ?string $idNumber): array
    {
        LoggerService::info('Get Insured Details', extra: [
            'customer_type' => $customerType ?? null,
            'id_type' => $idType ?? null,
            'id_number' => $idNumber ?? null,
        ]);

        // Determine if entity or individual
        $resolvedCustomerType = $this->determineIfEntity($customerType, $idType, $idNumber) ? CustomerTypeEnum::Entity : CustomerTypeEnum::Individual;

        // Search for insured
        $insuredDetails = $this->searchInsured($resolvedCustomerType, $idType, $idNumber);

        // Prepare response
        $status = $insuredDetails !== null;
        $message = $this->getResponseMessage($resolvedCustomerType, $status);

        return [
            'status' => $status,
            'response' => $insuredDetails,
            'message' => $message,
        ];
    }

    private function determineIfEntity(?string $customerType, ?string $idType, ?string $idNumber): bool
    {
        // Check if explicitly set as entity
        if ($customerType == CustomerTypeEnum::Entity) {
            LoggerService::info('Customer type is Entity', extra: [
                'customer_type' => $customerType,
            ]);

            return true;
        }

        // If customer type is empty or null, check if id_type is tradeLicense
        if (empty($customerType) || is_null($customerType) || $customerType == 'null') {
            LoggerService::info('Customer type is empty or null', extra: [
                'customer_type' => $customerType ?? null,
                'id_type' => $idType ?? null,
                'id_number' => $idNumber ?? null,
            ]);

            return $idType === GenericRequestEnum::TRADE_LICENSE && ! empty($idNumber);
        }

        return false;
    }

    private function searchInsured(string $customerType, ?string $idType, ?string $idNumber): ?Insured
    {
        return Insured::with('insuredKyc')
            ->where('customer_type', $customerType)
            ->where('id_type', $idType)
            ->when($idType == GenericRequestEnum::EMIRATES_ID, function ($query) use ($idNumber) {
                $query->emiratesIdNumber($idNumber);
            })
            ->when($idType != GenericRequestEnum::EMIRATES_ID, function ($query) use ($idNumber) {
                $query->where('id_number', $idNumber);
            })
            ->first();
    }

    private function getResponseMessage(string $customerType, bool $found): string
    {
        $messages = [
            CustomerTypeEnum::Individual => [
                'found' => 'Customer found with the entered ID number',
                'not_found' => 'No Customer found with the entered ID number',
            ],
            CustomerTypeEnum::Entity => [
                'found' => 'Entity found with the entered Trade License number',
                'not_found' => 'No Entity found with the entered Trade License number',
            ],
        ];

        $messageType = $found ? 'found' : 'not_found';

        return $messages[$customerType][$messageType];
    }

    public function getInsuredDetailsByQuote(?int $customerId, int $quoteTypeId, int $quoteRequestId): ?CustomerInsured
    {
        if ($customerId === null) {
            LoggerService::info('No CustomerInsured record found - customer_id is null', [
                'customer_id' => $customerId,
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
            ]);

            return null;
        }

        $customerInsured = CustomerInsured::active()
            ->forQuote($quoteTypeId, $quoteRequestId)
            ->where('customer_id', $customerId)
            ->with(['customer', 'insured', 'insured.insuredKyc'])
            ->first();

        if (! $customerInsured) {
            LoggerService::info('No CustomerInsured record found', [
                'customer_id' => $customerId,
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
            ]);
        }

        return $customerInsured;
    }

    public function getInsuredWithKyc(mixed $insuredId): ?Insured
    {
        if (! $insuredId) {
            return null;
        }

        return Insured::with('insuredKyc')->find($insuredId);
    }
}
