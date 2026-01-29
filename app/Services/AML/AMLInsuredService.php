<?php

namespace App\Services\AML;

use App\Enums\CustomerTypeEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Services\AML\DTOs\AMLOperationResult;
use App\Services\Logger\LoggerService;

/**
 * Service for handling AML insured operations
 * Manages individual and entity insured search and retrieval with various ID types
 */
class AMLInsuredService
{
    /**
     * Get insured details by customer type and identification
     *
     * @param  string|null  $code  For logging purposes
     */
    public function getInsuredDetails(
        ?string $customerType,
        ?string $idType,
        ?string $idNumber,
        ?string $tradeLicense,
        ?string $code = null
    ): AMLOperationResult {
        if ($code) {
            LoggerService::startQuoteLogging($code, LoggerFeatureEnum::AML_SCREENING);
        }

        LoggerService::info(self::class.' fn: '.__FUNCTION__, extra: [
            'customer_type' => $customerType,
            'id_type' => $idType,
            'id_number' => $idNumber,
            'trade_license' => $tradeLicense,
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

        return $status
            ? AMLOperationResult::success($insuredDetails, $message)
            : AMLOperationResult::failure($message);
    }

    /**
     * Determine if the search is for an entity or individual
     */
    private function determineIfEntity(?string $customerType, ?string $tradeLicense): bool
    {
        // Check if explicitly set as entity
        if ($customerType == CustomerTypeEnum::Entity) {
            return true;
        }

        // If customer type is empty or null, check if trade license is provided
        if (empty($customerType) || is_null($customerType) || $customerType == 'null') {
            return ! empty($tradeLicense);
        }

        return false;
    }

    /**
     * Search for insured based on customer type and identification
     */
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

    /**
     * Get appropriate response message based on customer type and status
     */
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

    /**
     * Get insured details by customer ID, quote type ID, and quote request ID
     * Used in AML quote details page to retrieve customer-insured mapping
     */
    public function getInsuredDetailsByQuote(int $customerId, int $quoteTypeId, int $quoteRequestId): ?CustomerInsured
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

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
