<?php

namespace App\Services\AML;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Services\Logger\LoggerService;

class AMLInsuredService
{
    public function getInsuredDetails(?string $customerType, ?string $idType, ?string $idNumber, ?string $tradeLicense): array
    {
        LoggerService::info('Get Insured Details', extra: [
            'customer_type' => $customerType ?? null,
            'id_type' => $idType ?? null,
            'id_number' => $idNumber ?? null,
            'trade_license' => $tradeLicense ?? null,
        ]);

        // Determine if entity or individual
        $isEntity = $this->determineIfEntity($customerType, $tradeLicense);
        $resolvedCustomerType = $isEntity ? CustomerTypeEnum::Entity : CustomerTypeEnum::Individual;

        // Search for insured
        $insuredDetails = $this->searchInsured(
            $resolvedCustomerType,
            $isEntity,
            $idType,
            $idNumber,
            $tradeLicense
        );

        // Prepare response
        $status = (bool) $insuredDetails;
        $message = $this->getResponseMessage($resolvedCustomerType, $status);

        return [
            'status' => $status,
            'response' => $insuredDetails,
            'message' => $message,
        ];
    }

    private function determineIfEntity(?string $customerType, ?string $tradeLicense): bool
    {
        // Check if explicitly set as entity
        if ($customerType == CustomerTypeEnum::Entity) {
            LoggerService::info('Customer type is Entity', extra: [
                'customer_type' => $customerType,
            ]);

            return true;
        }

        // If customer type is empty or null, check if trade license is provided
        if (empty($customerType) || is_null($customerType) || $customerType == 'null') {
            LoggerService::info('Customer type is empty or null', extra: [
                'customer_type' => $customerType ?? null,
                'trade_license' => $tradeLicense ?? null,
            ]);

            return ! empty($tradeLicense);
        }

        return false;
    }

    private function searchInsured(
        string $customerType,
        bool $isEntity,
        ?string $idType,
        ?string $idNumber,
        ?string $tradeLicense
    ): ?Insured {
        return Insured::with('insuredKyc')
            ->where('customer_type', $customerType)
            ->when($isEntity, function ($query) use ($tradeLicense) {
                $query->where('trade_license_no', $tradeLicense);
            })
            ->when(! $isEntity, function ($query) use ($idType, $idNumber) {
                $query->where('id_type', $idType)
                    ->when($idType == 'emiratesId', function ($query) use ($idNumber) {
                        $query->emiratesIdNumber($idNumber);
                    })
                    ->when($idType != 'emiratesId', function ($query) use ($idNumber) {
                        $query->where('id_number', $idNumber);
                    });
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

        $customerInsured = CustomerInsured::where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
            'customer_id' => $customerId,
        ])
            ->with(['customer', 'insured', 'insured.insuredKyc'])
            ->latest('updated_at')
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
}
