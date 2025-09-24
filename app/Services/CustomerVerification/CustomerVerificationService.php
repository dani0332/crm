<?php

declare(strict_types=1);

namespace App\Services\CustomerVerification;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerVerificationStatus;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\CustomerVerificationDetail;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Database\Eloquent\Model;

class CustomerVerificationService
{
    use GenericQueriesAllLobs;

    private $isCustomerVerificationEnabled = null;
    private $documentTypeCode = null;

    const VERIFICATION_FIELDS = [
        'year_of_manufacture',
        'dob',
        'nationality_id',
        'car_make_id',
        'car_model_id',
        'uae_license_held_for_id',
        'emirate_of_registration_id',
    ];

    private function handleUnsupportedQuoteType(QuoteTypes $quoteType): array
    {
        LoggerService::warning('Unsupported quote type for verification', extra: [
            'quote_type' => $quoteType->value,
        ]);

        return ['webForm' => [], 'customerVerified' => [], 'buttonData' => ['shouldShow' => false]];
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

    public function evaluateFieldUpdate($record, array $changedFields, QuoteTypes $quoteType): void
    {
        LoggerService::startQuoteLogging($record->code ?? null);

        $this->createVerificationSnapshot($record, $changedFields, $quoteType);

        LoggerService::info('Customer verification evaluation completed', extra: [
            'changed_fields' => $changedFields,
            'quote_type' => $quoteType->value,
        ]);
    }

    private function createVerificationSnapshot($record, array $changedFields, QuoteTypes $quoteType): void
    {
        if ($quoteType !== QuoteTypes::CAR) {
            return;
        }

        try {
            CustomerVerificationDetail::create([
                'quotable_type' => $quoteType->modelClass(),
                'quotable_id' => $record->id,
                'quote_type_id' => $quoteType->id(),
                'nationality_id' => $record->nationality_id,
                'vehicle_make_id' => $record->car_make_id,
                'vehicle_model_id' => $record->car_model_id,
                'year_of_manufacture' => $record->year_of_manufacture,
                'date_of_birth' => $record->dob,
                'emirate_of_registration_id' => $record->emirate_of_registration_id,
                'uae_license_held_for_id' => $record->uae_license_held_for_id,
            ]);

            LoggerService::info('Verification snapshot created');
        } catch (Exception $e) {
            LoggerService::warning('Failed to create verification snapshot', extra: [
                'error' => $e->getMessage(),
            ]);
        }
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
        $status = $this->determineVerificationStatus($record, $customerVerifiedData, $webFormData);

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

            if (! $verificationRecord) {
                LoggerService::info('No customer verification record found');

                return $this->getEmptyVerificationData($quoteType);
            }

            return [
                'nationality' => $verificationRecord->nationality?->text ?? '',
                'carMakeAndModel' => trim(($verificationRecord->carMake?->text ?? '').' '.($verificationRecord->carModel?->text ?? '')),
                'carModelYear' => $verificationRecord->year_of_manufacture ?? '',
                'dob' => $this->formatDateToDisplay($verificationRecord->date_of_birth),
                'emirateOfRegistration' => $verificationRecord->emirate?->text ?? '',
                'uaeLicenseHeldFor' => $verificationRecord->uaeLicenseHeldFor?->text ?? '',
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

        $verificationData['date_of_birth'] = $ocrData['dateOfBirth'];

        $nationalityId = $this->getNationalityId($ocrData['nationality']);
        if ($nationalityId) {
            $verificationData['nationality_id'] = $nationalityId;
        } else {
            $verificationData['nationality_id'] = null;
        }

        $verificationData['name'] = $ocrData['name'];

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

        $verificationData['carMakeAndModel'] = $ocrData['vehicalType'];
        $verificationData['carModelYear'] = $ocrData['vehicalModel'];

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
