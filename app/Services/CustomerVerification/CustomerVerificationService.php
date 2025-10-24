<?php

declare(strict_types=1);

namespace App\Services\CustomerVerification;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerVerificationStatus;
use App\Enums\LeadSourceEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Events\CustomerVerificationUpdated;
use App\Models\CarQuote;
use App\Models\CustomerVerificationDetail;
use App\Models\RegistrationCertificate;
use App\Models\VehicleDriverDetail;
use App\Services\CapiService;
use App\Services\CarQuoteService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

class CustomerVerificationService
{
    use GenericQueriesAllLobs;

    private $isCustomerVerificationEnabled = null;
    private $documentTypeCode = null;

    public function __construct(
        private CapiService $capiService,
        private CarQuoteService $carQuoteService
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
            'nationality' => ['value' => '', 'error' => false],
            'carMakeAndModel' => ['value' => '', 'error' => false],
            'carModelYear' => ['value' => '', 'error' => false],
            'dob' => ['value' => '', 'error' => false],
            'emirateOfRegistration' => ['value' => '', 'error' => false],
            'uaeLicenseHeldFor' => ['value' => '', 'error' => false],
        ];
    }

    private function getEmptyRegistrationCertificateData(QuoteTypes $quoteType): array
    {
        return match ($quoteType) {
            QuoteTypes::CAR => $this->getEmptyCarRegistrationCertificateData(),
            default => [],
        };
    }

    private function getEmptyVehicleDriverDetailsData(QuoteTypes $quoteType): array
    {
        return match ($quoteType) {
            QuoteTypes::CAR => $this->getEmptyCarVehicleDriverDetailsData(),
            default => [],
        };
    }

    private function getEmptyCarRegistrationCertificateData(): array
    {
        return [
            'placeOfIssue' => ['value' => '', 'error' => false],
        ];
    }

    private function getEmptyCarVehicleDriverDetailsData(): array
    {
        return [
            'driverLicenseIssueDate' => ['value' => '', 'error' => false],
        ];
    }

    private function getButtonConfigForStatus(CustomerVerificationStatus $status): array
    {
        return $status->getButtonConfig();
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

            return ['webForm' => [], 'customerVerified' => [], 'registrationCertificate' => [], 'vehicleDriverDetails' => [], 'buttonData' => ['shouldShow' => false]];
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
            'name' => "{$record->first_name} {$record->last_name}",
            'nationality' => $record->nationality_id_text ?? '',
            'carMakeAndModel' => trim(($record->car_make_id_text ?? '').' '.($record->car_model_id_text ?? '')),
            'carModelYear' => $record->year_of_manufacture ?? '',
            'dob' => $this->formatDateToDisplay($record->dob ?? null),
            'emirateOfRegistration' => $record->emirate_of_registration_id_text ?? '',
            'uaeLicenseHeldFor' => $record->uae_license_held_for_id_text ?? '',
        ];

        $customerVerifiedData = $this->getCustomerVerifiedDetails($record, QuoteTypes::CAR);

        return [
            'webForm' => $webFormData,
            'customerVerified' => $customerVerifiedData,
            'registrationCertificate' => $this->getRegistrationCertificateDetails($record, QuoteTypes::CAR),
            'vehicleDriverDetails' => $this->getVehicleDriverDetails($record, QuoteTypes::CAR),
            'buttonData' => $this->getVerificationButtonData($record, $webFormData, $customerVerifiedData),
        ];
    }

    public function getRegistrationCertificateDetails($record, QuoteTypes $quoteType): array
    {
        try {
            $registrationCertificateRecord = RegistrationCertificate::forQuotable($quoteType->modelClass(), $record->id)
                ->select('place_of_issue')
                ->first();

            if (! $registrationCertificateRecord) {
                LoggerService::info('No registration certificate record found');

                return $this->getEmptyRegistrationCertificateData($quoteType);
            }

            return [
                'placeOfIssue' => [
                    'value' => $registrationCertificateRecord->place_of_issue,
                    'error' => $this->verifyWithWebForm($registrationCertificateRecord->place_of_issue, $record->emirate_of_registration_id_text),
                ],
            ];

        } catch (Exception $e) {
            LoggerService::warning('Error fetching registration certificate details', extra: [
                'error' => $e->getMessage(),
            ]);

            return $this->getEmptyRegistrationCertificateData($quoteType);
        }
    }

    public function getVehicleDriverDetails($record, QuoteTypes $quoteType): array
    {
        try {
            $vehicleDriverDetailsRecord = VehicleDriverDetail::forQuotable($quoteType->modelClass(), $record->id)
                ->select('driver_license_issue_date')
                ->first();

            if (! $vehicleDriverDetailsRecord || ! $vehicleDriverDetailsRecord->driver_license_issue_date) {
                LoggerService::info('No vehicle driver details record found');

                return $this->getEmptyVehicleDriverDetailsData($quoteType);
            }

            // Calculate difference in years between driver license issue date and current date
            $driverLicenseIssueDate = Carbon::parse($vehicleDriverDetailsRecord->driver_license_issue_date);
            $yearsDifference = (int) $driverLicenseIssueDate->diffInYears(Carbon::now());

            // If less than 1, calculate difference in months
            if ($yearsDifference < 1) {
                $yearsDifference = (int) $driverLicenseIssueDate->diffInMonths(Carbon::now()).' months';
            } else {
                $yearsDifference = "{$yearsDifference} years";
            }

            // Format date for display
            $formattedDriverLicenseIssueDate = $driverLicenseIssueDate->format('d/m/Y');

            return [
                'driverLicenseIssueDate' => [
                    'value' => "{$yearsDifference} ({$formattedDriverLicenseIssueDate})",
                    'error' => $this->verifyLicenseHeldFor($yearsDifference, $record->uae_license_held_for_id_text),
                ],
            ];
        } catch (Exception $e) {
            LoggerService::warning('Error fetching vehicle driver details', extra: [
                'error' => $e->getMessage(),
            ]);

            return $this->getEmptyVehicleDriverDetailsData($quoteType);
        }
    }

    public function getCustomerVerifiedDetails($record, QuoteTypes $quoteType): array
    {
        try {
            $verificationRecord = CustomerVerificationDetail::with([
                'nationality',
                'carMake',
                'carModel',
                'emirate',
                'uaeLicenseHeldFor',
            ])
                ->forQuotable($quoteType->modelClass(), $record->id)
                ->where('quote_type_id', $quoteType->id())
                ->latest()
                ->first();

            if (! $verificationRecord || ! $verificationRecord->customer_verified_data) {
                LoggerService::info('No customer verification record found');

                return $this->getEmptyVerificationData($quoteType);
            }

            $customerVerifiedData = json_decode($verificationRecord->customer_verified_data, true);

            return [
                'name' => [
                    'value' => array_key_exists('name', $customerVerifiedData)
                    ? $customerVerifiedData['name']
                    : '',
                    'error' => $this->verifyWithWebForm($customerVerifiedData['name'], "{$record->first_name} {$record->last_name}"),
                ],
                'nationality' => [
                    'value' => array_key_exists('nationality_id', $customerVerifiedData)
                    ? $this->getNationalityById($customerVerifiedData['nationality_id'])
                    : '',
                    'error' => $this->verifyWithWebForm((int)$customerVerifiedData['nationality_id'], $record->nationality_id),
                ],
                'carMakeAndModel' => [
                    'value' => array_key_exists('carMakeAndModel', $customerVerifiedData)
                    ? trim($customerVerifiedData['carMakeAndModel'] ?? '')
                    : '',
                    'error' => $this->verifyWithWebForm($customerVerifiedData['carMakeAndModel'], "{$record->car_make_id_text} {$record->car_model_id_text}"),
                ],
                'carModelYear' => [
                    'value' => array_key_exists('carModelYear', $customerVerifiedData)
                    ? $customerVerifiedData['carModelYear']
                    : '',
                    'error' => $this->verifyWithWebForm($customerVerifiedData['carModelYear'], $record->year_of_manufacture),
                ],
                'dob' => [
                    'value' => array_key_exists('date_of_birth', $customerVerifiedData)
                    ? $this->formatDateToDisplay($customerVerifiedData['date_of_birth'])
                    : '',
                    'error' => $this->verifyWithWebForm(Carbon::parse($customerVerifiedData['date_of_birth'])->format('d-m-Y'), $record->dob),
                ],
            ];

        } catch (Exception $e) {
            LoggerService::warning('Error fetching customer verification details', extra: [
                'error' => $e->getMessage(),
            ]);

            return $this->getEmptyVerificationData($quoteType);
        }
    }

    private function verifyWithWebForm(string|int $ocrValue, string|int $webFormValue): bool
    {
        return $ocrValue !== $webFormValue;
    }

    private function verifyLicenseHeldFor(string $ocrLicenseHeldFor, string $webFormLicenseHeldFor): bool
    {
        // dd($ocrLicenseHeldFor, $webFormLicenseHeldFor);
        $ocrLicenseHeldForData = explode(' ', $ocrLicenseHeldFor);  // 2 years or 4 months
        $webFormLicenseHeldForData = explode(' ', $webFormLicenseHeldFor); // 3 years or 0 to 6 months

        // Check for years
        if ($ocrLicenseHeldForData[1] === $webFormLicenseHeldForData[1]) {
            return $ocrLicenseHeldForData[0] !== $webFormLicenseHeldForData[0];
        }

        if ($ocrLicenseHeldForData[1] === 'months' && $webFormLicenseHeldForData[3] === 'months') {
            return ! in_array($ocrLicenseHeldForData[0], range($webFormLicenseHeldForData[0], $webFormLicenseHeldForData[2]));
        }

        return true; // true is mismatch
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
            $dateOfBirth = $this->extractOcrValue($ocrData, 'dateOfBirth');
            if (! empty($dateOfBirth)) {
                $verificationData['date_of_birth'] = $dateOfBirth;
            }
        }

        if ($this->hasOcrKey($ocrData, 'nationality')) {
            $nationality = $this->extractOcrValue($ocrData, 'nationality');
            if (! empty($nationality)) {
                $nationalityId = $this->getNationalityId($nationality);
                if ($nationalityId) {
                    $verificationData['nationality_id'] = $nationalityId;
                }
            }
        }

        if ($this->hasOcrKey($ocrData, 'name')) {
            $name = $this->extractOcrValue($ocrData, 'name');
            if (! empty($name)) {
                $verificationData['name'] = $name;
            }
        }

        if (empty($verificationData)) {
            LoggerService::info('No valid customer verification data to update from Emirates ID OCR', extra: [
                'document_type' => $documentType,
                'quote_id' => $quote->id,
                'quote_code' => $quote->code ?? null,
                'quote_type' => QuoteTypes::CAR->value,
            ]);

            return;
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
            $verificationData['chassisNumber'] = $this->extractOcrValue($ocrData, 'chassisNumber');

            if ($verificationData['chassisNumber'] && $verificationData['carMakeAndModel']) {
                $this->saveVehicleChassisDetails($quote, ['chassis_number' => $verificationData['chassisNumber'], 'vehicle_make_model' => $verificationData['carMakeAndModel']]);
            }
        }

        if ($this->hasOcrKey($ocrData, 'vehicalModel')) {
            $vehicleModel = $this->extractOcrValue($ocrData, 'vehicalModel');
            if (! empty($vehicleModel)) {
                $verificationData['carModelYear'] = $vehicleModel;
            }
        }

        if (empty($verificationData)) {
            LoggerService::info('No valid customer verification data to update from RC OCR', extra: [
                'document_type' => $documentType,
                'quote_id' => $quote->id,
                'quote_code' => $quote->code ?? null,
                'quote_type' => QuoteTypes::CAR->value,
            ]);

            return;
        }

        try {
            $this->saveCustomerVerificationDetails($verificationData, $quote, $documentType);
        } catch (Exception $e) {
            LoggerService::warning('Failed to update customer verification details from RC OCR', extra: [
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

        LoggerService::info('Customer verification found:'.json_encode($data));
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

    private function saveVehicleChassisDetails(Model $quote, array $data): void
    {
        LoggerService::info('Saving vehicle chassis details', $data);
        $this->carQuoteService->saveVehicleChassisDetails($quote->uuid, $data);
    }

    private function updateCustomerVerificationStatus(Model $quote): void
    {
        $requestData = ['quoteUuid' => $quote->uuid,
            'quoteTypeId' => QuoteTypes::getId(QuoteTypes::CAR),
            'callSource' => LeadSourceEnum::IMCRM,
        ];

        LoggerService::info('Capi service request data', extra: $requestData);

        $response = $this->capiService->request('/api/customer/documents-verify', 'PUT', $requestData);

        LoggerService::info('Capi service response', extra: [
            'response' => $response,
        ]);

        $verificationSuccess = $response && ! isset($response->error) && isset($response->message);

        LoggerService::info('Customer verification status updated, broadcasting event', extra: [
            'quote_uuid' => $quote->uuid,
            'quote_type' => QuoteTypes::CAR->value,
            'verification_success' => $verificationSuccess,
            'has_response' => $response !== null,
        ]);

        event(new CustomerVerificationUpdated($quote->uuid, $verificationSuccess, QuoteTypes::CAR->value));
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
