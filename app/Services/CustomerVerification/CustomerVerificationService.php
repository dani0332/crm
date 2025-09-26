<?php

declare(strict_types=1);

namespace App\Services\CustomerVerification;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerVerificationStatus;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\CustomerVerificationDetail;
use App\Services\CapiService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Database\Eloquent\Model;

class CustomerVerificationService
{
    use GenericQueriesAllLobs;

    private $isCustomerVerificationEnabled = null;
    private $documentTypeCode = null;


    public function __construct(
        private CapiService $capiService
    ) {}

    private function handleUnsupportedQuoteType(QuoteTypes $quoteType): array
    {
        LoggerService::warning('Unsupported quote type for verification', extra: [
            'quote_type' => $quoteType->value,
        ]);

        return ['webForm' => [], 'customerVerified' => [], 'buttonData' => ['shouldShow' => false]];
    }

    private function extractOcrValue(array $ocrData, string $key, $default = null)
    {
        return $ocrData[$key] ?? $default;
    }

    private function hasOcrKey(array $ocrData, string $key): bool
    {
        return array_key_exists($key, $ocrData);
    }

    private function getEmptyVerificationData(QuoteTypes $quoteType): array
    {
        return match ($quoteType) {
            QuoteTypes::CAR => $this->getEmptyCarVerificationData(),
            default => [],
        };
    }

    private function getEmptyCarVerificationData(): array
    {
        return [
            'nationality' => '',
            'carMakeAndModel' => '',
            'carModelYear' => '',
            'dob' => '',
            'emirateOfRegistration' => '',
            'uaeLicenseHeldFor' => '',
        ];
    }

    private function getButtonConfigForStatus(CustomerVerificationStatus $status): array
    {
        return $status->getButtonConfig();
    }


    private function determineVerificationStatus($record, array $customerVerifiedData, array $webFormData): ?CustomerVerificationStatus
    {
        $hasCustomerData = ! empty(array_filter($customerVerifiedData));

        if (! $hasCustomerData) {
            return null;
        }

        if (isset($record->is_customer_data_valid) && $record->is_customer_data_valid === CustomerVerificationStatus::VERIFIED->value) {
            return CustomerVerificationStatus::VERIFIED;
        }

        $allFieldsMatch = true;
        foreach ($webFormData as $field => $webValue) {
            $customerValue = $customerVerifiedData[$field] ?? '';

            $normalizedWeb = trim(strtolower((string) $webValue));
            $normalizedCustomer = trim(strtolower((string) $customerValue));

            if ($normalizedWeb !== $normalizedCustomer) {
                $allFieldsMatch = false;
                break;
            }
        }

        return $allFieldsMatch ? CustomerVerificationStatus::VERIFIED : CustomerVerificationStatus::REQUIRES_VERIFICATION;
    }

    private function getVerificationButtonData($record, array $webFormData, array $customerVerifiedData): array
    {
        $status = $record->is_customer_data_valid === 1
            ? CustomerVerificationStatus::VERIFIED
            : ($record->is_customer_data_valid === 0
                ? CustomerVerificationStatus::REQUIRES_VERIFICATION
                : null);
        
        if ($status === null) {
            return [
                'status' => null,
                'text' => '',
                'color' => '',
                'class' => '',
                'shouldShow' => false,
            ];
        }

        $buttonConfig = $this->getButtonConfigForStatus($status);

        return [
            'status' => $status->value,
            'text' => $buttonConfig['text'],
            'color' => $buttonConfig['color'],
            'class' => $buttonConfig['class'],
            'shouldShow' => true,
        ];
    }

    public function getVerificationData($record, QuoteTypes $quoteType): array
    {
        if (! $record || ! isset($record->id)) {
            LoggerService::warning('Invalid quote record provided for verification', extra: [
                'record' => $record ? get_class($record) : 'null',
                'quote_type' => $quoteType->value,
            ]);

            return ['webForm' => [], 'customerVerified' => [], 'buttonData' => ['shouldShow' => false]];
        }

        LoggerService::startQuoteLogging($record->code ?? null);

        return match ($quoteType) {
            QuoteTypes::CAR => $this->getCarVerificationData($record),
            default => $this->handleUnsupportedQuoteType($quoteType),
        };
    }

    private function getCarVerificationData($record): array
    {
        $webFormData = [
            'nationality' => $record->nationality_id_text ?? '',
            'carMakeAndModel' => trim(($record->car_make_id_text ?? '').' '.($record->car_model_id_text ?? '')),
            'carModelYear' => $record->year_of_manufacture ?? '',
            'dob' => $this->formatDateToDisplay($record->dob ?? null),
            'emirateOfRegistration' => $record->emirate_of_registration_id_text ?? '',
            'uaeLicenseHeldFor' => $record->uae_license_held_for_id_text ?? '',
        ];

        $customerVerifiedData = $this->getCustomerVerifiedDetails($record->id, QuoteTypes::CAR);

        $verificationButtonData = $this->getVerificationButtonData($record, $webFormData, $customerVerifiedData);

        return [
            'webForm' => $webFormData,
            'customerVerified' => $customerVerifiedData,
            'buttonData' => $verificationButtonData,
        ];
    }

    public function getCustomerVerifiedDetails(int $quoteId, QuoteTypes $quoteType): array
    {
        try {
            $verificationRecord = CustomerVerificationDetail::with([
                'nationality',
                'carMake',
                'carModel',
                'emirate',
                'uaeLicenseHeldFor',
            ])
                ->forQuotable($quoteType->modelClass(), $quoteId)
                ->where('quote_type_id', $quoteType->id())
                ->latest()
                ->first();

            if (! $verificationRecord || ! $verificationRecord->customer_verified_data) {
                LoggerService::info('No customer verification record found');

                return $this->getEmptyVerificationData($quoteType);
            }

            $customerVerifiedData = json_decode($verificationRecord->customer_verified_data, true);
            return [
                'nationality' =>  array_key_exists('nationality_id', $customerVerifiedData)
                    ? $this->getNationalityById($customerVerifiedData['nationality_id'])
                    : '',
                'carMakeAndModel' => array_key_exists('carMakeAndModel', $customerVerifiedData)
                    ? trim($customerVerifiedData['carMakeAndModel'] ?? '')
                    : '',
                'carModelYear' => array_key_exists('carModelYear', $customerVerifiedData)
                    ? $customerVerifiedData['carModelYear']
                    : '',
                'dob' => array_key_exists('date_of_birth', $customerVerifiedData)
                    ? $this->formatDateToDisplay($customerVerifiedData['date_of_birth'])
                    : '',
            ];

        } catch (Exception $e) {
            LoggerService::warning('Error fetching customer verification details', extra: [
                'error' => $e->getMessage(),
            ]);

            return $this->getEmptyVerificationData($quoteType);
        }
    }

    public function processEmiratesIdVerification($quote, QuoteTypes $quoteType, array $ocrData, string $documentType): void
    {
        match ($quoteType) {
            QuoteTypes::CAR => $this->processCarEmiratesIdVerification($quote, $ocrData, $documentType),
            // Add other quote types here as needed
            default => $this->handleUnsupportedVerification($quoteType, $documentType, 'Emirates'),
        };
    }

    public function processMulkiyaVerification($quote, QuoteTypes $quoteType, array $ocrData, string $documentType): void
    {
        match ($quoteType) {
            QuoteTypes::CAR => $this->processCarMulkiyaVerification($quote, $ocrData, $documentType),
            // Add other quote types here as needed
            default => $this->handleUnsupportedVerification($quoteType, $documentType, 'Mulkiya'),
        };
    }

    private function processCarEmiratesIdVerification($quote, array $ocrData, string $documentType): void
    {
        $verificationData = [];

        if ($this->hasOcrKey($ocrData, 'dateOfBirth')) {
            $verificationData['date_of_birth'] = $this->extractOcrValue($ocrData, 'dateOfBirth');
        }

        if ($this->hasOcrKey($ocrData, 'nationality')) {
            $nationality = $this->extractOcrValue($ocrData, 'nationality');
            $nationalityId = $this->getNationalityId($nationality);
            if ($nationalityId) {
                $verificationData['nationality_id'] = $nationalityId;
            } else {
                $verificationData['nationality_id'] = null;
            }
        }

        if ($this->hasOcrKey($ocrData, 'name')) {
            $verificationData['name'] = $this->extractOcrValue($ocrData, 'name');
        }

        try {
            $this->saveCustomerVerificationDetails($verificationData, $quote, $documentType);
        } catch (Exception $e) {
            LoggerService::warning('Failed to update customer verification details from Emirates ID OCR', extra: [
                'document_type' => $documentType,
                'quote_id' => $quote->id,
                'quote_code' => $quote->code ?? null,
                'quote_type' => QuoteTypes::CAR->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function processCarMulkiyaVerification($quote, array $ocrData, string $documentType): void
    {
        $verificationData = [];

        if ($this->hasOcrKey($ocrData, 'vehicalType')) {
            $verificationData['carMakeAndModel'] = $this->extractOcrValue($ocrData, 'vehicalType');
        }

        if ($this->hasOcrKey($ocrData, 'vehicalModel')) {
            $verificationData['carModelYear'] = $this->extractOcrValue($ocrData, 'vehicalModel');
        }

        try {
            $this->saveCustomerVerificationDetails($verificationData, $quote, $documentType);
        } catch (Exception $e) {
            LoggerService::warning('Failed to update customer verification details from Emirates ID OCR', extra: [
                'document_type' => $documentType,
                'quote_id' => $quote->id,
                'quote_code' => $quote->code ?? null,
                'quote_type' => QuoteTypes::CAR->value,
                'error' => $e->getMessage(),
            ]);
        }

    }

    private function saveCustomerVerificationDetails(array $verificationData, Model $quote, string $documentType): void
    {
        $data = CustomerVerificationDetail::where('quotable_type', QuoteTypes::CAR->modelClass())
            ->where('quotable_id', $quote->id)
            ->where('quote_type_id', QuoteTypes::CAR->id())
            ->first();

        LoggerService::info('Customer verification found:'.$data);
        if ($data) {
            $existingData = json_decode($data->customer_verified_data, true);
            $existingData = array_merge($existingData, $verificationData);

            $data->update(['customer_verified_data' => json_encode($existingData)]);
        } else {
            CustomerVerificationDetail::create([
                'quotable_type' => QuoteTypes::CAR->modelClass(),
                'quotable_id' => $quote->id,
                'quote_type_id' => QuoteTypes::CAR->id(),
                'customer_verified_data' => json_encode($verificationData),
            ]);
        }

        LoggerService::info("Customer verification details updated from {$documentType} OCR", extra: [
            'document_type' => $documentType,
            'quote_id' => $quote->id,
            'quote_code' => $quote->code ?? null,
            'quote_type' => QuoteTypes::CAR->value,
            'updated_fields' => array_keys($verificationData),
        ]);

        // Update customer verification status
        $this->updateCustomerVerificationStatus($quote);

    }

    private function updateCustomerVerificationStatus(Model $quote): void
    {
        $requestData = ['quoteUuid' => $quote->uuid,
            'quoteTypeId' => QuoteTypes::getId(QuoteTypes::CAR),
        ];

        LoggerService::info('Capi service request data', extra: $requestData);

        $response = $this->capiService->request('/api/customer/documents-verify', 'PUT', $requestData);

        LoggerService::info('Capi service response', extra: [
            'response' => $response,
        ]);
    }

    private function handleUnsupportedVerification(QuoteTypes $quoteType, string $documentType, string $documentTypeText): void
    {
        LoggerService::info("{$documentTypeText} verification not supported for quote type", extra: [
            'document_type' => $documentType,
            'quote_type' => $quoteType->value,
        ]);
    }

    public function isCustomerVerificationEnabled(): bool
    {
        if ($this->isCustomerVerificationEnabled === null) {
            $this->isCustomerVerificationEnabled = getAppStorageValueByKey(ApplicationStorageEnums::CUSTOMER_VERIFICATION_ENABLED, useCache: true) == '1';
        }

        return $this->isCustomerVerificationEnabled;
    }

    public function processOcrVerification(Model $quote, object $data, string $documentType): void
    {
        $this->documentTypeCode = $documentType;
        if (! $this->isCustomerVerificationEnabled()) {
            LoggerService::info('Customer verification feature disabled - skipping verification processing', extra: [
                'quote_uuid' => $quote->uuid,
                'document_type' => $this->documentTypeCode,
                'feature_flag' => 'CUSTOMER_VERIFICATION_ENABLED',
            ]);

            return;
        }

        $quoteType = match (true) {
            $quote instanceof CarQuote => QuoteTypes::CAR,
            // Add other quote types here as needed
            default => null,
        };

        if ($quoteType) {
            switch ($this->documentTypeCode) {
                case OCRDocumentTypeEnum::ID_CARD->value:
                    $this->processEmiratesIdVerification($quote, $quoteType, (array) $data, $this->documentTypeCode);
                    break;
                case OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE->value:
                    $this->processMulkiyaVerification($quote, $quoteType, (array) $data, $this->documentTypeCode);
                    break;
                default:
                    break;
            }
        } else {
            LoggerService::info('Customer verification not supported for quote type', extra: [
                'quote_uuid' => $quote->uuid,
                'quote_class' => get_class($quote),
                'document_type' => $this->documentTypeCode,
            ]);
        }
    }
}
