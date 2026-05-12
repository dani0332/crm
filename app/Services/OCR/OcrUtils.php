<?php

declare(strict_types=1);

namespace App\Services\OCR;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\DocumentType;
use App\Models\HealthQuote;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use App\Services\AccuracyMatrixService;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait OcrUtils
{
    private static function quoteTypesOcrWithoutPayment(): array
    {
        return [
            QuoteTypes::getId(QuoteTypes::HEALTH),
        ];
    }

    public function getCleanData(array $data): array
    {
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    public function getFieldsToUpdate(array $fieldsToUpdate): array
    {
        $dataToUpdate = [];
        foreach ($fieldsToUpdate as $field => $value) {
            if ($value !== null && $value !== '') {
                $dataToUpdate[$field] = $value;
            }
        }

        return $dataToUpdate;
    }

    public function formatDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (Exception $e) {
            LoggerService::error('Failed to format date', exception: $e);

            return null;
        }
    }

    public function extractFirstName(?string $fullName): ?string
    {
        if (empty($fullName)) {
            return null;
        }

        $nameParts = explode(' ', trim($fullName));

        return $nameParts[0] ?? null;
    }

    public function extractLastName(?string $fullName): ?string
    {
        if (empty($fullName)) {
            return null;
        }

        $nameParts = explode(' ', trim($fullName));
        if (count($nameParts) > 1) {
            // Join all parts except the first as last name
            return implode(' ', array_slice($nameParts, 1));
        }

        return null;
    }

    public function formatGender(?string $gender): ?string
    {
        if (empty($gender)) {
            return null;
        }

        return match (strtoupper(trim($gender))) {
            'M', 'MALE' => 'male',
            'F', 'FEMALE' => 'female',
            default => $gender
        };
    }

    public function ensureArray($data): array
    {
        if (is_object($data)) {
            return (array) $data;
        }

        return is_array($data) ? $data : [];
    }

    public function resolveProp($object, $prop)
    {
        if (is_object($object) && property_exists($object, $prop)) {
            return $object->$prop;
        }

        return null;
    }

    public function parseDate($date, $default = null, $format = 'Y-m-d')
    {
        try {
            return $date ? Carbon::parse($date)->format($format) : $default;
        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during date parsing: ', exception: $e);

            return $default;
        }
    }

    public function getProvider(Model $quote)
    {
        $quoteTypeId = $this->getQuoteTypeId($quote);
        $isOcrWithoutPayment = in_array($quoteTypeId, self::quoteTypesOcrWithoutPayment());

        // Check if quoet type does not require payment for OCR
        if ($isOcrWithoutPayment) {
            return $quote->insuranceProvider?->code ?? null;
        }

        if ($quote instanceof SendUpdateLog) {
            return $quote->insuranceProvider?->code ?? null;
        }

        if ($quote->payment?->insuranceProvider) {
            return $quote->payment?->insuranceProvider?->code ?? null;
        }

        // Fallback case for regular quotes
        if ($quote->payments && $quote->payments->isNotEmpty()) {
            $firstPayment = $quote->payments->first();
            if ($firstPayment?->insuranceProvider) {
                return $firstPayment->insuranceProvider->code;
            }
        }

        return null;
    }

    /**
     * Update Accuracy Matrix cache after successful OCR processing
     */
    public function updateAccuracyMatrix(
        QuoteTypes $quoteType,
        Model $quote,
        OCRDocumentTypeEnum $docType,
        object $data,
        DocumentType $documentType
    ): void {
        $accuracyMatrixService = app(AccuracyMatrixService::class);

        LoggerService::info('OcrUtils::updateAccuracyMatrix called', [
            'quote_id' => $quote->id,
            'quote_type' => $quoteType->value,
            'doc_type' => $docType->value,
        ]);

        if (! $accuracyMatrixService->isEligibleQuote($quote, $quoteType)) {
            LoggerService::info('OcrUtils::updateAccuracyMatrix - Quote not eligible', [
                'quote_id' => $quote->id,
                'quote_type' => $quoteType->value,
            ]);

            return;
        }

        if (! $this->requiresOcrNotifications($docType)) {
            LoggerService::info('OcrUtils::updateAccuracyMatrix - Document type does not require OCR notifications', [
                'quote_id' => $quote->id,
                'quote_type' => $quoteType->value,
                'doc_type' => $docType->value,
            ]);

            return;
        }

        LoggerService::info('OcrUtils::updateAccuracyMatrix - Document eligible for accuracy matrix', [
            'quote_id' => $quote->id,
            'quote_type' => $quoteType->value,
            'doc_type' => $docType->value,
            'document_type_id' => $documentType->id,
            'document_type_code' => $documentType->code,
        ]);

        $policyNumber = $accuracyMatrixService->extractPolicyNumberFromOcrData($data, $docType);
        $docId = $this->generateDocumentId($quote, $documentType);

        LoggerService::info('OcrUtils::updateAccuracyMatrix - Extracted policy number', [
            'quote_id' => $quote->id,
            'quote_type' => $quoteType->value,
            'doc_type' => $docType->value,
            'policy_number' => $policyNumber,
            'doc_id' => $docId,
        ]);

        $accuracyMatrixService->updateDocumentData(
            $quote->id,
            $quoteType->value,
            $docType,
            $docId,
            $policyNumber,
            true
        );

        LoggerService::info('OcrUtils::updateAccuracyMatrix - Document data updated in accuracy matrix', [
            'quote_id' => $quote->id,
            'quote_type' => $quoteType->value,
            'doc_type' => $docType->value,
            'policy_number' => $policyNumber,
            'doc_id' => $docId,
        ]);
    }

    /**
     * Check if the document type requires OCR notifications.
     * Returns true for any document type that is enabled for OCR processing.
     */
    public function requiresOcrNotifications(OCRDocumentTypeEnum $docType): bool
    {
        LoggerService::info('OcrUtils::requiresOcrNotifications - Start', [
            'doc_type' => $docType->value,
        ]);

        // Get all enabled types across all quote types
        $allEnabledTypes = [];

        foreach (QuoteTypes::cases() as $quoteType) {
            $enabledTypes = OCRDocumentTypeEnum::getEnabledTypes($quoteType);
            // Convert enum objects to their string values for comparison
            $enabledTypeValues = array_map(fn ($type) => $type->value, $enabledTypes);
            $allEnabledTypes = array_merge($allEnabledTypes, $enabledTypeValues);
        }

        // Remove duplicates and check if the document type's value is in the enabled list
        $allEnabledTypes = array_unique($allEnabledTypes);

        $isEnabled = in_array($docType->value, $allEnabledTypes);

        LoggerService::info('OcrUtils::requiresOcrNotifications - Result', [
            'doc_type' => $docType->value,
            'is_enabled' => $isEnabled,
            'all_enabled_types' => $allEnabledTypes,
        ]);

        return $isEnabled;
    }

    private function generateDocumentId(Model $quote, DocumentType $documentType): string
    {
        $docId = "{$documentType->code}_{$quote->uuid}_".uniqid();

        LoggerService::info('OcrUtils::generateDocumentId', [
            'quote_id' => $quote->id,
            'quote_uuid' => $quote->uuid,
            'document_type_code' => $documentType->code,
            'document_id' => $docId,
        ]);

        return $docId;
    }

    public function checkIfQuoteTypeIsGroupMedical(QuoteTypes $quoteType): QuoteTypes
    {
        if ($quoteType == QuoteTypes::GROUP_MEDICAL) {
            return QuoteTypes::BUSINESS;
        }

        return $quoteType;
    }

    public function isSendUpdateEligibleForOCR($quote, $isSendUpdate)
    {
        // Home & Group Medical only for Send Update, not allowed for other LOBs
        $allowedLOBs = [
            QuoteTypes::getId(QuoteTypes::HOME),
            QuoteTypes::getId(QuoteTypes::GROUP_MEDICAL),
        ];

        // Check if this is a Business quote (ID 5) that's actually Group Medical
        $isGroupMedicalBusiness = $this->isGroupMedicalBusiness($quote);

        $isEligible = $isSendUpdate && $quote instanceof SendUpdateLog &&
                     (in_array($quote->quote_type_id, $allowedLOBs) || $isGroupMedicalBusiness);

        LoggerService::info('isSendUpdateEligibleForOCR - Eligibility Check', [
            'is_send_update' => $isSendUpdate,
            'is_send_update_log_instance' => $quote instanceof SendUpdateLog,
            'quote_type_id' => $quote->quote_type_id ?? 'N/A',
            'quote_uuid' => $quote->uuid ?? 'N/A',
            'quote_code' => $quote->code ?? 'N/A',
            'allowed_lob_ids' => $allowedLOBs,
            'is_lob_allowed' => $quote instanceof SendUpdateLog ? in_array($quote->quote_type_id, $allowedLOBs) : false,
            'is_group_medical_business' => $isGroupMedicalBusiness,
            'final_eligibility' => $isEligible,
            'eligibility_reason' => $isEligible ? 'Eligible for OCR' : $this->getIneligibilityReason($quote, $isSendUpdate, $allowedLOBs),
        ]);

        return $isEligible;
    }

    private function getIneligibilityReason($quote, $isSendUpdate, $allowedLOBs)
    {
        if (! $isSendUpdate) {
            return 'Not a Send Update';
        }

        if (! ($quote instanceof SendUpdateLog)) {
            return 'Quote is not a SendUpdateLog instance';
        }

        if (! in_array($quote->quote_type_id, $allowedLOBs)) {
            return 'LOB not allowed for Send Update OCR (only HOME and GROUP_MEDICAL allowed)';
        }

        return 'Unknown reason';
    }

    public function isGroupMedicalBusiness($quote)
    {
        try {
            // Handle direct BusinessQuote instances
            if ($quote instanceof BusinessQuote) {
                return $quote->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
            }

            // Handle SendUpdateLog instances
            if ($quote instanceof SendUpdateLog && $quote->quote_type_id == QuoteTypes::getId(QuoteTypes::BUSINESS)) {
                $actualQuote = BusinessQuote::where('uuid', $quote->quote_uuid)->first();

                return $actualQuote && $actualQuote->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
            }

            return false;
        } catch (Exception $e) {
            LoggerService::error('Error checking Group Medical business type', [
                'error' => $e->getMessage(),
                'quote_type' => get_class($quote),
                'quote_id' => $quote->id ?? 'N/A',
                'quote_uuid' => $quote->uuid ?? 'N/A',
            ]);

            return false;
        }
    }

    public function extractProviderCode(Model $quote): ?string
    {
        $providerCode = null;
        $quoteTypeId = $this->getQuoteTypeId($quote);
        $isOcrWithoutPayment = in_array($quoteTypeId, self::quoteTypesOcrWithoutPayment());
        $extractionSource = 'none';

        // Check if quote type does not require payment for OCR
        if ($isOcrWithoutPayment) {
            $providerCode = $quote->insuranceProvider?->code ?? null;
            if ($providerCode) {
                $extractionSource = 'insuranceProvider';
            }
        }

        // First priority: Check payments for all model types if not OCR without payment
        if (! $isOcrWithoutPayment && $quote->payments && $quote->payments->isNotEmpty()) {
            $latestPayment = $quote->payments->first();
            if ($latestPayment && $latestPayment->insuranceProvider) {
                $providerCode = $latestPayment->insuranceProvider->code;
                $extractionSource = 'payments';
            }
        }

        // Second priority: For SendUpdateLog, use insuranceProvider if payments didn't yield a result
        if ($providerCode === null && $quote instanceof SendUpdateLog && $quote->insuranceProvider) {
            $providerCode = $quote->insuranceProvider->code;
            $extractionSource = 'insuranceProvider';
        }

        // Log the result for debugging
        LoggerService::info('Provider code extraction result - Quote UUID: '.$quote->uuid, [
            'model_type' => get_class($quote),
            'provider_code' => $providerCode,
            'has_insurance_provider' => $quote instanceof SendUpdateLog ? isset($quote->insuranceProvider) : false,
            'has_payments' => $quote->payments && $quote->payments->isNotEmpty(),
            'is_ocr_without_payment' => $isOcrWithoutPayment,
            'extraction_source' => $extractionSource,
        ]);

        return $providerCode;
    }

    public function getQuoteTypeId($quote): int
    {
        return match (true) {
            $quote instanceof CarQuote => (int) QuoteTypes::CAR->id(),
            $quote instanceof HealthQuote => (int) QuoteTypes::HEALTH->id(),
            $quote instanceof PersonalQuote => $quote->quote_type_id,
            $quote instanceof BusinessQuote => (int) QuoteTypes::BUSINESS->id(),
            default => $quote->quote_type_id,
        };
    }

    public function getRefId(Model $quote): string
    {
        if ($quote instanceof SendUpdateLog) {
            $prefix = QuoteTypes::getName($quote->quote_type_id)->shortCode();

            return $prefix.$quote->code;
        }

        return $quote->code;
    }

    public function extractPlateCodeNumber(?string $plateNumber): ?array
    {
        if (empty($plateNumber)) {
            return null;
        }

        $cleaned = trim($plateNumber);

        // $parts = explode('/', $cleaned, 2);
        if (preg_match('/^([A-Z0-9]+)[\/:\-\s\']*(\d+)$/i', $cleaned, $matches)) {
            LoggerService::info('OCR Utils - extractPlateCodeNumber - Plate code and number extracted', extra: [
                'plate_number' => $plateNumber,
                'matches' => $matches,
            ]);

            return [
                'plate_code' => $matches[1],
                'plate_number' => $matches[2],
            ];
        }

        return [
            'plate_code' => null,
            'plate_number' => null,
        ];
    }

    public function getVehicleColorCode(?string $vehicleColor, int $quoteTypeId, ?int $providerId): ?string
    {
        LoggerService::info('OCR Utils - getVehicleColorCode called', extra: [
            'vehicleColor' => $vehicleColor,
            'quoteTypeId' => $quoteTypeId,
            'providerId' => $providerId,
        ]);

        if (empty($vehicleColor)) {
            LoggerService::info('OCR Utils - Vehicle color is empty, returning null');

            return null;
        }

        if (! $providerId) {
            LoggerService::warning('OCR Utils - No valid provider id for vehicle color code', extra: [
                'vehicleColor' => $vehicleColor,
                'quoteTypeId' => $quoteTypeId,
                'providerId' => $providerId,
            ]);

            return null;
        }

        $vehicleColors = app(LookupService::class)->getVehicleColors($quoteTypeId, $providerId);

        $matchedColor = $vehicleColors->first(function ($color) use ($vehicleColor) {
            return strtolower($color->text) === strtolower($vehicleColor);
        });

        LoggerService::info('OCR Utils - Vehicle color lookup result', extra: [
            'vehicleColor' => $vehicleColor,
            'providerId' => $providerId,
            'availableColors' => $vehicleColors->pluck('text')->toArray(),
            'matchedColor' => $matchedColor?->code,
            'matchedColorText' => $matchedColor?->text,
        ]);

        return $matchedColor?->code ?? null;
    }

    public function getBankCode(?string $bankName, int $quoteTypeId, ?int $providerId): ?string
    {
        if (empty($bankName)) {
            return null;
        }

        if (! $providerId) {
            LoggerService::warning('OCR Utils - No valid provider id for bank code');

            return null;
        }

        $banks = app(LookupService::class)->getBankNames($quoteTypeId, $providerId);

        return $banks->first(function ($bank) use ($bankName) {
            return strtolower($bank->text) === strtolower($bankName);
        })?->code ?? null;
    }

    public function getIssuancePlaceCode(?string $issuancePlace): ?string
    {
        if (empty($issuancePlace)) {
            return null;
        }

        $issuancePlaces = app(LookupService::class)->getIssuancePlaces();

        return $issuancePlaces->first(function ($place) use ($issuancePlace) {
            return strtolower($place->text) === strtolower($issuancePlace);
        })?->code ?? null;
    }

    protected function getNationalityId(?string $nationality): ?int
    {
        if (empty($nationality)) {
            return null;
        }
        LoggerService::info('Getting nationality ID for nationality: '.$nationality);

        $cacheKey = 'customer_verification_nationality_'.md5((string) $nationality);
        $nationalityModel = cache()->remember(
            $cacheKey,
            now()->addDay(),
            fn () => Nationality::where(function ($query) use ($nationality) {
                $query->where('text', 'LIKE', '%'.$nationality.'%')
                    ->orWhere('country_name', 'LIKE', '%'.$nationality.'%')
                    ->orWhere('code', $nationality);
            })->first(['id'])
        );

        return $nationalityModel?->id;
    }
}
